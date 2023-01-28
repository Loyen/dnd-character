<?php

namespace loyen\DndbCharacterSheet\Model;

class Item implements \JsonSerializable
{
    private int $id; // id
    private int $typeId; // entityTypeId
    private string $name; // name
    private ?string $type; // filterType
    private ?string $subType = null; // subType || type
    private ?int $quantity = null; // quantity

    private ?int $armorClass = null; // armorClass
    private ?int $armorTypeId = null; // armorTypeId

    private ?string $damageType = null; //damageType
    private ?string $damage = null; // damage.diceString

    private ?int $range = null; // range
    private ?int $longRange = null; // longRange

    private bool $canAttune = false; // canAttune
    private bool $isAttuned = false; // isAttuned
    private bool $isConsumable = false; // isConsumable
    private bool $isEquipped = false; // canEquip && isEquip
    private bool $isMagical = false; // magic
    private array $properties = []; // properties

    /**
     * @var array<int, int> $modifierIds
     */
    private array $modifierIds = []; // grantedModifiers.id

    public function __construct(string $name, ?string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setTypeId(int $typeId): void
    {
        $this->typeId = $typeId;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function setSubType(?string $subType): void
    {
        $this->subType = $subType;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setArmorClass(int $armorClass): void
    {
        $this->armorClass = $armorClass;
    }

    public function getArmorClass(): ?int
    {
        return $this->armorClass;
    }

    public function setArmorTypeId(int $armorTypeId): void
    {
        $this->armorTypeId = $armorTypeId;
    }

    public function getArmorTypeId(): ?int
    {
        return $this->armorTypeId;
    }

    public function setDamage(?string $damage): void
    {
        $this->damage = $damage;
    }

    public function getDamage(): ?string
    {
        return $this->damage;
    }

    public function setDamageType(?string $damageType): void
    {
        $this->damageType = $damageType;
    }

    public function getDamageType(): ?string
    {
        return $this->damageType;
    }

    public function setRange(?int $range): void
    {
        $this->range = $range;
    }

    public function getRange(): ?int
    {
        return $this->range;
    }

    public function setLongRange(?int $longRange): void
    {
        $this->longRange = $longRange;
    }

    public function getLongRange(): ?int
    {
        return $this->longRange;
    }

    public function setCanAttune(bool $canAttune): void
    {
        $this->canAttune = $canAttune;
    }

    public function canBeAttuned(): bool
    {
        return $this->canAttune;
    }

    public function setIsAttuned(bool $isAttuned): void
    {
        $this->isAttuned = $isAttuned;
    }

    public function isAttuned(): bool
    {
        return $this->isAttuned;
    }

    public function setIsConsumable(bool $isConsumable): void
    {
        $this->isConsumable = $isConsumable;
    }

    public function isConsumable(): bool
    {
        return $this->isConsumable;
    }

    public function setIsEquipped(bool $isEquipped): void
    {
        $this->isEquipped = $isEquipped;
    }

    public function isEquipped(): bool
    {
        return $this->isEquipped;
    }

    public function setIsMagical(bool $isMagical): void
    {
        $this->isMagical = $isMagical;
    }

    public function isMagical(): bool
    {
        return $this->isMagical;
    }

    /**
     * @param array<int, int> $modifierIds
     */
    public function setModifierIds(array $modifierIds): void
    {
        $this->modifierIds = $modifierIds;
    }

    /**
     * @return array<int, int>
     */
    public function getModifierIds(): array
    {
        return $this->modifierIds;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $property): void
    {
        $this->properties[] = $property;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'subType' => $this->subType,
            'quantity' => $this->quantity,
            'armorClass' => $this->armorClass,
            'damageType' => $this->damageType,
            'damage' => $this->damage,
            'range' => $this->range,
            'longRange' => $this->longRange,
            'canAttune' => $this->canAttune,
            'isAttuned' => $this->isAttuned,
            'isConsumable' => $this->isConsumable,
            'isEquipped' => $this->isEquipped,
            'isMagical' => $this->isMagical,
            'properties' => $this->properties
        ];
    }
}
