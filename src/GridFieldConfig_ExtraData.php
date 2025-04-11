<?php

namespace Fromholdio\GridFieldExtraData;

use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldConfigurablePaginator;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;

class GridFieldConfig_ExtraData extends GridFieldConfig
{
    public function __construct(
        ?string $orderableField = null,
        ?int $itemsPerPage = 20,
        bool $showPagination = true,
        bool $showAdd = true,
        ?array $extraData = null,
        bool $doWrite = false,
        ?bool $removeRelation = false
    ) {
        parent::__construct();

        $this->addComponents([
            new GridFieldButtonRow('before'),
            new GridFieldAddNewButton('buttons-before-left'),
            // new GridFieldToolbarHeader(),
            // $sort = new GridFieldSortableHeader(),
            // $filter = new GridFieldFilterHeader(),
            new GridFieldTitleHeader(),
            new GridFieldDataColumns(),
            new GridFieldEditButton()
        ]);

        if (!is_null($removeRelation)) {
            $deleteAction = new GridFieldDeleteAction($removeRelation);
            $this->addComponent($deleteAction);
        }

        if (!is_null($itemsPerPage)) {
            $pagination = new GridFieldPaginator($itemsPerPage);
            $this->addComponent($pagination);
        }

        $this->addComponent(new GridFieldExtraDataDetailForm(
            null,
            $showPagination,
            $showAdd,
            $extraData,
            $doWrite
        ));

        if ($orderableField) {
            $this->addComponent(new GridFieldOrderableRows($orderableField));
        }

        $this->extend('updateConfig');
    }

    public function addMultiAdder(array $classes): self
    {
        $adder = new GridFieldAddNewMultiClass();
        $adder->setClasses($classes);
        $this->removeComponentsByType(GridFieldAddNewButton::class);
        $this->addComponent($adder);
        return $this;
    }
}
