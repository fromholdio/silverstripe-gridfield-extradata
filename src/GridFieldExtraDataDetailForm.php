<?php

namespace Fromholdio\GridFieldExtraData;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filterable;

class GridFieldExtraDataDetailForm extends GridFieldDetailForm
{
    protected bool $doWrite;
    protected ?array $extraData;
    protected $doRedirect;

    public function __construct(
        ?string $name = null,
        ?bool $showPagination = null,
        ?bool $showAdd = null,
        ?array $extraData = null,
        bool $doWrite = false)
    {
        parent::__construct($name, $showPagination, $showAdd);
        $this->setExtraData($extraData);
        $this->setDoWrite($doWrite);
    }

    public function setDoWrite(bool $doWrite = false)
    {
        $this->doWrite = $doWrite;
        return $this;
    }

    public function getDoWrite() :bool
    {
        return (bool) $this->doWrite;
    }

    public function setExtraData(?array $extraData) :self
    {
        $this->extraData = $extraData;
        return $this;
    }

    public function getExtraData() :?array
    {
        return $this->extraData;
    }

    public function handleItem($gridField, $request)
    {
        // Our getController could either give us a true Controller, if this is the top-level GridField.
        // It could also give us a RequestHandler in the form of GridFieldDetailForm_ItemRequest if this is a
        // nested GridField.
        $requestHandler = $gridField->getForm()->getController();
        $record = $this->getRecordFromRequest($gridField, $request);
        if (!$record) {
            return $requestHandler->httpError(404, 'That record was not found');
        }
        $handler = $this->getItemRequestHandler($gridField, $record, $requestHandler);
        if ($this->doRedirect) {
            return $requestHandler->redirect($handler->Link());
        }
        $manager = $this->getStateManager();
        if ($gridStateStr = $manager->getStateFromRequest($gridField, $request)) {
            $gridField->getState(false)->setValue($gridStateStr);
        }

        // if no validator has been set on the GridField and the record has a
        // CMS validator, use that.
        if (!$this->getValidator() && ClassInfo::hasMethod($record, 'getCMSValidator')) {
            $this->setValidator($record->getCMSValidator());
        }

        return $handler->handleRequest($request);
    }

    public function handleNewRecord(DataObject $record): DataObject
    {
        $extraData = $this->getExtraData();
        if (is_array($extraData) && count($extraData) > 0) {
            foreach ($extraData as $key => $value) {
                $record->{$key} = $value;
            }
        }
        if ($this->getDoWrite()) {
            $record->write();
            $this->doRedirect = true;
        }
        return $record;
    }

    protected function getRecordFromRequest(GridField $gridField, HTTPRequest $request): ?DataObject
    {
        /** @var DataObject $record */
        if (is_numeric($request->param('ID'))) {
            /** @var Filterable $dataList */
            $dataList = $gridField->getList();
            $record = $dataList->byID($request->param('ID'));
        } else {
            $record = Injector::inst()->create($gridField->getModelClass());
            $record = $this->handleNewRecord($record);
        }
        return $record;
    }

    protected function getItemRequestHandler($gridField, $record, $requestHandler)
    {
        $class = $this->getItemRequestClass();
        $assignedClass = $this->itemRequestClass;
        $this->extend('updateItemRequestClass', $class, $gridField, $record, $requestHandler, $assignedClass);
        /** @var GridFieldDetailForm_ItemRequest $handler */
        $handler = Injector::inst()->createWithArgs(
            $class,
            array($gridField, $this, $record, $requestHandler, $this->name)
        );
        if ($template = $this->getTemplate()) {
            $handler->setTemplate($template);
        }
        $this->extend('updateItemRequestHandler', $handler);
        return $handler;
    }
}
