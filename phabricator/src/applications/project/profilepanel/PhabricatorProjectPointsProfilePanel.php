<?php

final class PhabricatorProjectPointsProfilePanel
  extends PhabricatorProfilePanel {

  const PANELKEY = 'project.points';

  public function getPanelTypeName() {
    return pht('Project Points');
  }

  private function getDefaultName() {
    return pht('Points Bar');
  }

  public function shouldEnableForObject($object) {
    $viewer = $this->getViewer();

    // Only render this element for milestones.
    if (!$object->isMilestone()) {
      return false;
    }

    // Don't show if points aren't configured.
    if (!ManiphestTaskPoints::getIsEnabled()) {
      return false;
    }

    // Points are only available if Maniphest is installed.
    $class = 'PhabricatorManiphestApplication';
    if (!PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
      return false;
    }

    return true;
  }

  public function getDisplayName(
    PhabricatorProfilePanelConfiguration $config) {
    return $this->getDefaultName();
  }

  public function buildEditEngineFields(
    PhabricatorProfilePanelConfiguration $config) {
    return array(
      id(new PhabricatorInstructionsEditField())
        ->setValue(
          pht(
            'This is a progress bar which shows how many points of work '.
            'are complete within the milestone. It has no configurable '.
            'settings.')),
    );
  }

  protected function newNavigationMenuItems(
    PhabricatorProfilePanelConfiguration $config) {
    $viewer = $this->getViewer();
    $project = $config->getProfileObject();

    $limit = 250;

    $tasks = id(new ManiphestTaskQuery())
      ->setViewer($viewer)
      ->withEdgeLogicPHIDs(
        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
        PhabricatorQueryConstraint::OPERATOR_AND,
        array($project->getPHID()))
      ->setLimit($limit + 1)
      ->execute();

    if (count($tasks) > $limit) {
      return $this->renderError(
        pht(
          'Too many tasks to compute statistics for (more than %s).',
          new PhutilNumber($limit)));
    }

    if (!$tasks) {
      return $this->renderError(
        pht(
          'This milestone has no tasks yet.'));
    }

    $statuses = array();
    $points_done = 0;
    $points_total = 0;
    $no_points = 0;
    foreach ($tasks as $task) {
      $points = $task->getPoints();

      if ($points === null) {
        $no_points++;
        continue;
      }

      if (!$points) {
        continue;
      }

      $status = $task->getStatus();
      if (empty($statuses[$status])) {
        $statuses[$status] = 0;
      }
      $statuses[$status] += $points;

      if (ManiphestTaskStatus::isClosedStatus($status)) {
        $points_done += $points;
      }

      $points_total += $points;
    }

    if ($no_points == count($tasks)) {
      return $this->renderError(
        pht('No tasks have assigned point values.'));
    }


    if (!$points_total) {
      return $this->renderError(
        pht('All tasks with assigned point values are worth zero points.'));
    }

    $label = pht(
      '%s of %s %s',
      new PhutilNumber($points_done),
      new PhutilNumber($points_total),
      ManiphestTaskPoints::getPointsLabel());

    $bar = id(new PHUISegmentBarView())
      ->setLabel($label);

    $map = ManiphestTaskStatus::getTaskStatusMap();
    $statuses = array_select_keys($statuses, array_keys($map));

    foreach ($statuses as $status => $points) {
      if (!$points) {
        continue;
      }

      if (!ManiphestTaskStatus::isClosedStatus($status)) {
        continue;
      }

      $color = ManiphestTaskStatus::getStatusColor($status);
      if (!$color) {
        $color = 'sky';
      }

      $tooltip = pht(
        '%s %s',
        new PhutilNumber($points),
        ManiphestTaskStatus::getTaskStatusName($status));

      $bar->newSegment()
        ->setWidth($points / $points_total)
        ->setColor($color)
        ->setTooltip($tooltip);
    }

    $bar = phutil_tag(
      'div',
      array(
        'class' => 'phui-profile-segment-bar',
      ),
      $bar);

    $item = $this->newItem()
      ->appendChild($bar);

    return array(
      $item,
    );
  }

  private function renderError($message) {
    $message = phutil_tag(
      'div',
      array(
        'class' => 'phui-profile-menu-error',
      ),
      $message);

    $item = $this->newItem()
      ->appendChild($message);

    return array(
      $item,
    );
  }

}
