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

final class PonderAnswer extends PonderDAO
  implements PhabricatorMarkupInterface, PonderVotableInterface {

  const MARKUP_FIELD_CONTENT = 'markup:content';

  protected $phid;
  protected $authorPHID;
  protected $questionID;

  protected $content;
  protected $contentSource;

  protected $voteCount;
  private $vote;
  private $question = null;

  public function setQuestion($question) {
    $this->question = $question;
    return $this;
  }

  public function getQuestion() {
    return $this->question;
  }

  public function setUserVote($vote) {
    $this->vote = $vote['data'];
    if (!$this->vote) {
      $this->vote = PonderConstants::NONE_VOTE;
    }
    return $this;
  }

  public function getUserVote() {
    return $this->vote;
  }

  public function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
    ) + parent::getConfiguration();
  }

  public function setTitle($title) {
    $this->title = $title;
    if (!$this->getID()) {
      $this->originalTitle = $title;
    }
    return $this;
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorPHIDConstants::PHID_TYPE_ANSW);
  }

  public function setContentSource(PhabricatorContentSource $content_source) {
    $this->contentSource = $content_source->serialize();
    return $this;
  }

  public function getContentSource() {
    return PhabricatorContentSource::newFromSerialized($this->contentSource);
  }

  public function getAnswers() {
    return $this->loadRelatives(new PonderAnswer(), "questionID");
  }

  public function getMarkupField() {
    return self::MARKUP_FIELD_CONTENT;
  }

  // Markup interface

  public function getMarkupFieldKey($field) {
    $hash = PhabricatorHash::digest($this->getMarkupText($field));
    $id = $this->getID();
    return "ponder:A{$id}:{$field}:{$hash}";
  }

  public function getMarkupText($field) {
    return $this->getContent();
  }

  public function newMarkupEngine($field) {
    return PhabricatorMarkupEngine::newPonderMarkupEngine();
  }

  public function didMarkupText(
    $field,
    $output,
    PhutilMarkupEngine $engine) {
    return $output;
  }

  public function shouldUseMarkupCache($field) {
    return (bool)$this->getID();
  }

  // votable interface
  public function getUserVoteEdgeType() {
    return PhabricatorEdgeConfig::TYPE_VOTING_USER_HAS_ANSWER;
  }

  public function getVotablePHID() {
    return $this->getPHID();
  }
}
