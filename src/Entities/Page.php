<?php
namespace FiveamCode\LaravelNotionApi\Entities;

use DateTime;
use FiveamCode\LaravelNotionApi\Entities\Properties\Checkbox;
use FiveamCode\LaravelNotionApi\Entities\Properties\Date;
use FiveamCode\LaravelNotionApi\Entities\Properties\Email;
use FiveamCode\LaravelNotionApi\Entities\Properties\MultiSelect;
use FiveamCode\LaravelNotionApi\Entities\Properties\Number;
use FiveamCode\LaravelNotionApi\Entities\Properties\People;
use FiveamCode\LaravelNotionApi\Entities\Properties\PhoneNumber;
use FiveamCode\LaravelNotionApi\Entities\Properties\Property;
use FiveamCode\LaravelNotionApi\Entities\Properties\Relation;
use FiveamCode\LaravelNotionApi\Entities\Properties\Select;
use FiveamCode\LaravelNotionApi\Entities\Properties\Text;
use FiveamCode\LaravelNotionApi\Entities\Properties\Title;
use FiveamCode\LaravelNotionApi\Entities\Properties\Url;
use FiveamCode\LaravelNotionApi\Exceptions\HandlingException;
use FiveamCode\LaravelNotionApi\Traits\HasArchive;
use FiveamCode\LaravelNotionApi\Traits\HasParent;
use FiveamCode\LaravelNotionApi\Traits\HasTimestamps;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Class Page.
 */
class Page extends Entity
{
    use HasTimestamps, HasArchive, HasParent;

    protected string $title = '';
    protected string $id = '';
    protected string $url = '';
    private string $icon = '';
    private string $iconType = '';
    private string $cover = '';
    private string $coverType = '';
    protected array $rawProperties = [];
    protected array $propertyMap = [];
    protected array $propertyKeys = [];
    protected Collection $properties;

    public function __construct(array $responseData = null)
    {
        $this->properties = new Collection();
        parent::__construct($responseData);
    }

    protected function setResponseData(array $responseData): void
    {
        parent::setResponseData($responseData);
        if ($responseData['object'] !== 'page') {
            throw HandlingException::instance('invalid json-array: the given object is not a page');
        }
        $this->id = $responseData['id']; // Asignación del ID de la página
        $this->fillFromRaw();
    }

    private function fillFromRaw(): void
    {
        parent::fillEssentials();
        $this->fillProperties();
        $this->fillTitle();
        $this->fillPageUrl();
        $this->fillIcon();
        $this->fillCover();
    }

    private function fillProperties(): void
    {
        if (Arr::exists($this->responseData, 'properties')) {
            $this->rawProperties = $this->responseData['properties'];
            $this->properties = new Collection();
            foreach (array_keys($this->rawProperties) as $propertyKey) {
                $property = Property::fromResponse($propertyKey, $this->rawProperties[$propertyKey]);
                $this->properties->add($property);
                $this->propertyMap[$propertyKey] = $property;
            }
            $this->propertyKeys = array_keys($this->rawProperties);
        }
    }

    private function fillTitle(): void
    {
        $titleProperty = $this->properties->filter(function ($property) {
            return $property->getType() == 'title';
        })->first();

        if ($titleProperty !== null) {
            $rawTitleProperty = $titleProperty->getRawContent();
            if (is_array($rawTitleProperty) && count($rawTitleProperty) >= 1) {
                if (Arr::exists($rawTitleProperty[0], 'plain_text')) {
                    $this->title = $rawTitleProperty[0]['plain_text'];
                }
            }
        }
    }

    private function fillIcon(): void
    {
        if (Arr::exists($this->responseData, 'icon') && $this->responseData['icon'] != null) {
            $this->iconType = $this->responseData['icon']['type'];
            if (Arr::exists($this->responseData['icon'], 'emoji')) {
                $this->icon = $this->responseData['icon']['emoji'];
            } elseif (Arr::exists($this->responseData['icon'], 'file')) {
                $this->icon = $this->responseData['icon']['file']['url'];
            } elseif (Arr::exists($this->responseData['icon'], 'external')) {
                $this->icon = $this->responseData['icon']['external']['url'];
            }
        }
    }

    private function fillCover(): void
    {
        if (Arr::exists($this->responseData, 'cover') && $this->responseData['cover'] != null) {
            $this->coverType = $this->responseData['cover']['type'];
            if (Arr::exists($this->responseData['cover'], 'file')) {
                $this->cover = $this->responseData['cover']['file']['url'];
            } elseif (Arr::exists($this->responseData['cover'], 'external')) {
                $this->cover = $this->responseData['cover']['external']['url'];
            }
        }
    }

    private function fillPageUrl(): void
    {
        if (Arr::exists($this->responseData, 'url')) {
            $this->url = $this->responseData['url'];
        }
    }

    public function set(string $propertyKey, Property $property): Page
    {
        $property->setTitle($propertyKey);
        $this->properties->add($property);
        $this->propertyMap[$propertyKey] = $property;

        if ($property instanceof Title) {
            $this->title = $property->getPlainText();
        }

        return $this;
    }

    public function setNumber(string $propertyTitle, float $number): Page
    {
        $this->set($propertyTitle, Number::value($number));
        return $this;
    }

    public function setTitle(string $propertyTitle, string $text): Page
    {
        $this->set($propertyTitle, Title::value($text));
        return $this;
    }

    public function setText(string $propertyTitle, string $text): Page
    {
        $this->set($propertyTitle, Text::value($text));
        return $this;
    }

    public function setSelect(string $propertyTitle, string $name): Page
    {
        $this->set($propertyTitle, Select::value($name));
        return $this;
    }

    public function setUrl(string $propertyTitle, string $url): Page
    {
        $this->set($propertyTitle, Url::value($url));
        return $this;
    }

    public function setPhoneNumber(string $propertyTitle, string $phoneNumber): Page
    {
        $this->set($propertyTitle, PhoneNumber::value($phoneNumber));
        return $this;
    }

    public function setEmail(string $propertyTitle, string $email): Page
    {
        $this->set($propertyTitle, Email::value($email));
        return $this;
    }

    public function setMultiSelect(string $propertyTitle, array $names): Page
    {
        $this->set($propertyTitle, MultiSelect::value($names));
        return $this;
    }

    public function setCheckbox(string $propertyTitle, bool $checked): Page
    {
        $this->set($propertyTitle, Checkbox::value($checked));
        return $this;
    }

    public function setDate(string $propertyTitle, DateTime $start, ?DateTime $end = null): Page
    {
        $this->set($propertyTitle, Date::value($start, $end));
        return $this;
    }

    public function setDateTime(string $propertyTitle, DateTime $start, ?DateTime $end = null): Page
    {
        $this->set($propertyTitle, Date::valueWithTime($start, $end));
        return $this;
    }

    public function setRelation(string $propertyTitle, array $relationIds): Page
    {
        $this->set($propertyTitle, Relation::value($relationIds));
        return $this;
    }

    public function setPeople(string $propertyTitle, array $userIds): Page
    {
        $this->set($propertyTitle, People::value($userIds));
        return $this;
    }

    public function getPageId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getIconType(): string
    {
        return $this->iconType;
    }

    public function getCover(): string
    {
        return $this->cover;
    }

    public function getCoverType(): string
    {
        return $this->coverType;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function getProperty(string $propertyKey): ?Property
    {
        return $this->propertyMap[$propertyKey] ?? null;
    }

    public function getRawProperties(): array
    {
        return $this->rawProperties;
    }

    public function getPropertyKeys(): array
    {
        return $this->propertyKeys;
    }
}
