<?php

final class PhabricatorCalendarImportEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'calendar.import';

  private $importEngine;

  public function setImportEngine(PhabricatorCalendarImportEngine $engine) {
    $this->importEngine = $engine;
    return $this;
  }

  public function getImportEngine() {
    return $this->importEngine;
  }

  public function getEngineName() {
    return pht('Calendar Imports');
  }

  public function isEngineConfigurable() {
    return false;
  }

  public function getSummaryHeader() {
    return pht('Configure Calendar Import Forms');
  }

  public function getSummaryText() {
    return pht('Configure how users create and edit imports.');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorCalendarApplication';
  }

  protected function newEditableObject() {
    $viewer = $this->getViewer();
    $engine = $this->getImportEngine();

    return PhabricatorCalendarImport::initializeNewCalendarImport(
      $viewer,
      $engine);
  }

  protected function newObjectQuery() {
    return new PhabricatorCalendarImportQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Import');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Import: %s', $object->getDisplayName());
  }

  protected function getObjectEditShortText($object) {
    return pht('Import %d', $object->getID());
  }

  protected function getObjectCreateShortText() {
    return pht('Create Import');
  }

  protected function getObjectName() {
    return pht('Import');
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('import/edit/');
  }

  protected function buildCustomEditFields($object) {
    $viewer = $this->getViewer();

    $fields = array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Name of the import.'))
        ->setTransactionType(
          PhabricatorCalendarImportNameTransaction::TRANSACTIONTYPE)
        ->setConduitDescription(pht('Rename the import.'))
        ->setConduitTypeDescription(pht('New import name.'))
        ->setValue($object->getName()),
      id(new PhabricatorBoolEditField())
        ->setKey('disabled')
        ->setOptions(pht('Active'), pht('Disabled'))
        ->setLabel(pht('Disabled'))
        ->setDescription(pht('Disable the import.'))
        ->setTransactionType(
          PhabricatorCalendarImportDisableTransaction::TRANSACTIONTYPE)
        ->setIsConduitOnly(true)
        ->setConduitDescription(pht('Disable or restore the import.'))
        ->setConduitTypeDescription(pht('True to cancel the import.'))
        ->setValue($object->getIsDisabled()),
    );

    $import_engine = $object->getEngine();
    foreach ($import_engine->newEditEngineFields($this, $object) as $field) {
      $fields[] = $field;
    }

    return $fields;
  }


}
