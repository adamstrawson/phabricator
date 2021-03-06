<?php

/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class PhabricatorFactHomeController extends PhabricatorFactController {

  public function processRequest() {
    $request = $this->getRequest();
    $user = $request->getUser();

    if ($request->isFormPost()) {
      $uri = new PhutilURI('/fact/chart/');
      $uri->setQueryParam('y1', $request->getStr('y1'));
      return id(new AphrontRedirectResponse())->setURI($uri);
    }

    $types = array(
      '+N:*',
      '+N:DREV',
      'updated',
    );

    $engines = PhabricatorFactEngine::loadAllEngines();
    $specs = PhabricatorFactSpec::newSpecsForFactTypes($engines, $types);

    $facts = id(new PhabricatorFactAggregate())->loadAllWhere(
      'factType IN (%Ls)',
      $types);

    $rows = array();
    foreach ($facts as $fact) {
      $spec = $specs[$fact->getFactType()];

      $name = $spec->getName();
      $value = $spec->formatValueForDisplay($user, $fact->getValueX());

      $rows[] = array(
        phutil_escape_html($name),
        phutil_escape_html($value),
      );
    }

    $table = new AphrontTableView($rows);
    $table->setHeaders(
      array(
        'Fact',
        'Value',
      ));
    $table->setColumnClasses(
      array(
        'wide',
        'n',
      ));

    $panel = new AphrontPanelView();
    $panel->setHeader('Facts!');
    $panel->appendChild($table);

    $chart_form = $this->buildChartForm();

    return $this->buildStandardPageResponse(
      array(
        $chart_form,
        $panel,
      ),
      array(
        'title' => 'Facts!',
      ));
  }

  private function buildChartForm() {
    $request = $this->getRequest();
    $user = $request->getUser();

    $table = new PhabricatorFactRaw();
    $conn_r = $table->establishConnection('r');
    $table_name = $table->getTableName();

    $facts = queryfx_all(
      $conn_r,
      'SELECT DISTINCT factType from %T',
      $table_name);

    $specs = PhabricatorFactSpec::newSpecsForFactTypes(
      PhabricatorFactEngine::loadAllEngines(),
      ipull($facts, 'factType'));

    $options = array();
    foreach ($specs as $spec) {
      if ($spec->getUnit() == PhabricatorFactSpec::UNIT_COUNT) {
        $options[$spec->getType()] = $spec->getName();
      }
    }

    if (!$options) {
      return id(new AphrontErrorView())
        ->setSeverity(AphrontErrorView::SEVERITY_NOTICE)
        ->setTitle(pht('No Chartable Facts'))
        ->appendChild(
          '<p>'.pht(
            'There are no facts that can be plotted yet.').'</p>');
    }

    $form = id(new AphrontFormView())
      ->setUser($user)
      ->appendChild(
        id(new AphrontFormSelectControl())
          ->setLabel('Y-Axis')
          ->setName('y1')
          ->setOptions($options))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue('Plot Chart'));

    $panel = new AphrontPanelView();
    $panel->appendChild($form);
    $panel->setWidth(AphrontPanelView::WIDTH_FORM);
    $panel->setHeader('Plot Chart');

    return $panel;
  }

}
