<?php

final class PhabricatorDashboardPanelRenderController
  extends PhabricatorDashboardController {

  private $id;

  public function willProcessRequest(array $data) {
    $this->id = $data['id'];
  }

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $panel = id(new PhabricatorDashboardPanelQuery())
      ->setViewer($viewer)
      ->withIDs(array($this->id))
      ->executeOne();
    if (!$panel) {
      return new Aphront404Response();
    }

    if ($request->isAjax()) {
      $parent_phids = $request->getStrList('parentPanelPHIDs', null);
      if ($parent_phids === null) {
        throw new Exception(
          pht(
            'Required parameter `parentPanelPHIDs` is not present in '.
            'request.'));
      }
    } else {
      $parent_phids = array();
    }

    $rendered_panel = id(new PhabricatorDashboardPanelRenderingEngine())
      ->setViewer($viewer)
      ->setPanel($panel)
      ->setParentPanelPHIDs($parent_phids)
      ->setHeaderless($request->getBool('headerless'))
      ->renderPanel();

    if ($request->isAjax()) {
      return id(new AphrontAjaxResponse())
        ->setContent(
          array(
            'panelMarkup' => hsprintf('%s', $rendered_panel),
          ));
    }

    $crumbs = $this->buildApplicationCrumbs()
      ->addTextCrumb(pht('Panels'), $this->getApplicationURI('panel/'))
      ->addTextCrumb($panel->getMonogram(), '/'.$panel->getMonogram())
      ->addTextCrumb(pht('Standalone View'));

    return $this->buildApplicationPage(
      array(
        $crumbs,
        $rendered_panel,
      ),
      array(
        'title' => array(pht('Panel'), $panel->getName()),
        'device' => true,
      ));
  }

}
