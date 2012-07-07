<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Search\Lucene\Search\Query;

use Zend\Search\Lucene\Index;
use Zend\Search\Lucene;
use Zend\Search\Lucene\Search\Weight;
use Zend\Search\Lucene\Search\Highlighter\HighlighterInterface as Highlighter;
use Zend\Search\Lucene\Exception\InvalidArgumentException;
	
/**
 * A Query that matches documents containing a particular sequence of terms.
 *
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Phrase extends AbstractQuery
{
    /**
     * Terms to find.
     * Array of Zend_Search_Lucene_Index_Term objects.
     *
     * @var array
     */
    private $_terms;

    /**
     * Term positions (relative positions of terms within the phrase).
     * Array of integers
     *
     * @var array
     */
    private $_offsets;

    /**
     * Sets the number of other words permitted between words in query phrase.
     * If zero, then this is an exact phrase search.  For larger values this works
     * like a WITHIN or NEAR operator.
     *
     * The slop is in fact an edit-distance, where the units correspond to
     * moves of terms in the query phrase out of position.  For example, to switch
     * the order of two words requires two moves (the first move places the words
     * atop one another), so to permit re-orderings of phrases, the slop must be
     * at least two.
     * More exact matches are scored higher than sloppier matches, thus search
     * results are sorted by exactness.
     *
     * The slop is zero by default, requiring exact matches.
     *
     * @var integer
     */
    private $_slop;

    /**
     * Result vector.
     *
     * @var array
     */
    private $_resVector = null;

    /**
     * Terms positions vectors.
     * Array of Arrays:
     * term1Id => (docId => array( pos1, pos2, ... ), ...)
     * term2Id => (docId => array( pos1, pos2, ... ), ...)
     *
     * @var array
     */
    private $_termsPositions = array();

    /**
     * Class constructor.  Create a new prase query.
     *
     * @param string $field    Field to search.
     * @param array  $terms    Terms to search Array of strings.
     * @param array  $offsets  Relative term positions. Array of integers.
     * @throws \Zend\Search\Lucene\Exception\InvalidArgumentException
     */
    public function __construct($terms = null, $offsets = null, $field = null)
    {
        $this->_slop = 0;

        if (is_array($terms)) {
            $this->_terms = array();
            foreach ($terms as $termId => $termText) {
                $this->_terms[$termId] = ($field !== null)? new Index\Term($termText, $field):
                                                            new Index\Term($termText);
            }
        } else if ($terms === null) {
            $this->_terms = array();
        } else {
            throw new InvalidArgumentException('terms argument must be array of strings or null');
        }

        if (is_array($offsets)) {
            if (count($this->_terms) != count($offsets)) {
                throw new InvalidArgumentException('terms and offsets arguments must have the same size.');
            }
            $this->_offsets = $offsets;
        } else if ($offsets === null) {
            $this->_offsets = array();
            foreach ($this->_terms as $termId => $term) {
                $position = count($this->_offsets);
                $this->_offsets[$termId] = $position;
            }
        } else {
            throw new InvalidArgumentException('offsets argument must be array of strings or null');
        }
    }

    /**
     * Set slop
     *
     * @param integer $slop
     */
    public function setSlop($slop)
    {
        $this->_slop = $slop;
    }


    /**
     * Get slop
     *
     * @return integer
     */
    public function getSlop()
    {
        return $this->_slop;
    }


    /**
     * Adds a term to the end of the query phrase.
     * The relative position of the term is specified explicitly or the one immediately
     * after the last term added.
     *
     * @param \Zend\Search\Lucene\Index\Term $term
     * @param integer $position
     * @throws \Zend\Search\Lucene\Exception\InvalidArgumentException
     */
    public function addTerm(Index\Term $term, $position = null) {
        if ((count($this->_terms) != 0)&&(end($this->_terms)->field != $term->field)) {
            throw new InvalidArgumentException('All phrase terms must be in the same field: ' .
                                                   $term->field . ':' . $term->text);
        }

        $this->_terms[] = $term;
        if ($position !== null) {
            $this->_offsets[] = $position;
        } else if (count($this->_offsets) != 0) {
            $this->_offsets[] = end($this->_offsets) + 1;
        } else {
            $this->_offsets[] = 0;
        }
    }


    /**
     * Re-write query into primitive queries in the context of specified index
     *
     * @param \Zend\Search\Lucene\SearchIndexInterface $index
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     */
    public function rewrite(Lucene\SearchIndexInterface $index)
    {
        if (count($this->_terms) == 0) {
            return new EmptyResult();
        } else if ($this->_terms[0]->field !== null) {
            return $this;
        } else {
            $query = new Boolean();
            $query->setBoost($this->getBoost());

            foreach ($index->getFieldNames(true) as $fieldName) {
                $subquery = new self();
                $subquery->setSlop($this->getSlop());

                foreach ($this->_terms as $termId => $term) {
                    $qualifiedTerm = new Index\Term($term->text, $fieldName);

                    $subquery->addTerm($qualifiedTerm, $this->_offsets[$termId]);
                }

                $query->addSubquery($subquery);
            }

            return $query;
        }
    }

    /**
     * Optimize query in the context of specified index
     *
     * @param \Zend\Search\Lucene\SearchIndexInterface $index
     * @return \Zend\Search\Lucene\Search\Query\AbstractQuery
     */
    public function optimize(Lucene\SearchIndexInterface $index)
    {
        // Check, that index contains all phrase terms
        foreach ($this->_terms as $term) {
            if (!$index->hasTerm($term)) {
                return new EmptyResult();
            }
        }

        if (count($this->_terms) == 1) {
            // It's one term query
            $optimizedQuery = new Term(reset($this->_terms));
            $optimizedQuery->setBoost($this->getBoost());

            return $optimizedQuery;
        }

        if (count($this->_terms) == 0) {
            return new EmptyResult();
        }


        return $this;
    }

    /**
     * Returns query term
     *
     * @return array
     */
    public function getTerms()
    {
        return $this->_terms;
    }


    /**
     * Set weight for specified term
     *
     * @param integer $num
     * @param \Zend\Search\Lucene\Search\Weight\Term $weight
     */
    public function setWeight($num, $weight)
    {
        $this->_weights[$num] = $weight;
    }


    /**
     * Constructs an appropriate Weight implementation for this query.
     *
     * @param \Zend\Search\Lucene\SearchIndexInterface $reader
     * @return \Zend\Search\Lucene\Search\Weight\Phrase
     */
    public function createWeight(Lucene\SearchIndexInterface $reader)
    {
        $this->_weight = new Weight\Phrase($this, $reader);
        return $this->_weight;
    }


    /**
     * Score calculator for exact phrase queries (terms sequence is fixed)
     *
     * @param integer $docId
     * @return float
     */
    public function _exactPhraseFreq($docId)
    {
        $freq = 0;

        // Term Id with lowest cardinality
        $lowCardTermId = null;

        // Calculate $lowCardTermId
        foreach ($this->_terms as $termId => $term) {
            if ($lowCardTermId === null ||
                count($this->_termsPositions[$termId][$docId]) <
                count($this->_termsPositions[$lowCardTermId][$docId]) ) {
                    $lowCardTermId = $termId;
                }
        }

        // Walk through positions of the term with lowest cardinality
        foreach ($this->_termsPositions[$lowCardTermId][$docId] as $lowCardPos) {
            // We expect phrase to be found
            $freq++;

            // Walk through other terms
            foreach ($this->_terms as $termId => $term) {
                if ($termId != $lowCardTermId) {
                    $expectedPosition = $lowCardPos +
                                            ($this->_offsets[$termId] -
                                             $this->_offsets[$lowCardTermId]);

                    if (!in_array($expectedPosition, $this->_termsPositions[$termId][$docId])) {
                        $freq--;  // Phrase wasn't found.
                        break;
                    }
                }
            }
        }

        return $freq;
    }

    /**
     * Score calculator for sloppy phrase queries (terms sequence is fixed)
     *
     * @param integer $docId
     * @param \Zend\Search\Lucene\SearchIndexInterface $reader
     * @return float
     */
    public function _sloppyPhraseFreq($docId, Lucene\SearchIndexInterface $reader)
    {
        $freq = 0;

        $phraseQueue = array();
        $phraseQueue[0] = array(); // empty phrase
        $lastTerm = null;

        // Walk through the terms to create phrases.
        foreach ($this->_terms as $termId => $term) {
            $queueSize = count($phraseQueue);
            $firstPass = true;

            // Walk through the term positions.
            // Each term position produces a set of phrases.
            foreach ($this->_termsPositions[$termId][$docId] as $termPosition ) {
                if ($firstPass) {
                    for ($count = 0; $count < $queueSize; $count++) {
                        $phraseQueue[$count][$termId] = $termPosition;
                    }
                } else {
                    for ($count = 0; $count < $queueSize; $count++) {
                        if ($lastTerm !== null &&
                            abs( $termPosition - $phraseQueue[$count][$lastTerm] -
                                 ($this->_offsets[$termId] - $this->_offsets[$lastTerm])) > $this->_slop) {
                            continue;
                        }

                        $newPhraseId = count($phraseQueue);
                        $phraseQueue[$newPhraseId]          = $phraseQueue[$count];
                        $phraseQueue[$newPhraseId][$termId] = $termPosition;
                    }

                }

                $firstPass = false;
            }
            $lastTerm = $termId;
        }


        foreach ($phraseQueue as $phrasePos) {
            $minDistance = null;

            for ($shift = -$this->_slop; $shift <= $this->_slop; $shift++) {
                $distance = 0;
                $start = reset($phrasePos) - reset($this->_offsets) + $shift;

                foreach ($this->_terms as $termId => $term) {
                    $distance += abs($phrasePos[$termId] - $this->_offsets[$termId] - $start);

                    if($distance > $this->_slop) {
                        break;
                    }
                }

                if ($minDistance === null || $distance < $minDistance) {
                    $minDistance = $distance;
                }
            }

            if ($minDistance <= $this->_slop) {
                $freq += $reader->getSimilarity()->sloppyFreq($minDistance);
            }
        }

        return $freq;
    }

    /**
     * Execute query in context of index reader
     * It also initializes necessary internal structures
     *
     * @param \Zend\Search\Lucene\SearchIndexInterface $reader
     * @param \Zend\Search\Lucene\Index\DocsFilter|null $docsFilter
     */
    public function execute(Lucene\SearchIndexInterface $reader, $docsFilter = null)
    {
        $this->_resVector = null;

        if (count($this->_terms) == 0) {
            $this->_resVector = array();
        }

        $resVectors      = array();
        $resVectorsSizes = array();
        $resVectorsIds   = array(); // is used to prevent arrays comparison
        foreach ($this->_terms as $termId => $term) {
            $resVectors[]      = array_flip($reader->termDocs($term));
            $resVectorsSizes[] = count(end($resVectors));
            $resVectorsIds[]   = $termId;

            $this->_termsPositions[$termId] = $reader->termPositions($term);
        }
        // sort resvectors in order of subquery cardinality increasing
        array_multisort($resVectorsSizes, SORT_ASC, SORT_NUMERIC,
                        $resVectorsIds,   SORT_ASC, SORT_NUMERIC,
                        $resVectors);

        foreach ($resVectors as $nextResVector) {
            if($this->_resVector === null) {
                $this->_resVector = $nextResVector;
            } else {
                //$this->_resVector = array_intersect_key($this->_resVector, $nextResVector);

                /**
                 * This code is used as workaround for array_intersect_key() slowness problem.
                 */
                $updatedVector = array();
                foreach ($this->_resVector as $id => $value) {
                    if (isset($nextResVector[$id])) {
                        $updatedVector[$id] = $value;
                    }
                }
                $this->_resVector = $updatedVector;
            }

            if (count($this->_resVector) == 0) {
                // Empty result set, we don't need to check other terms
                break;
            }
        }

        // ksort($this->_resVector, SORT_NUMERIC);
        // Docs are returned ordered. Used algorithm doesn't change elements order.

        // Initialize weight if it's not done yet
        $this->_initWeight($reader);
    }

    /**
     * Get document ids likely matching the query
     *
     * It's an array with document ids as keys (performance considerations)
     *
     * @return array
     */
    public function matchedDocs()
    {
        return $this->_resVector;
    }

    /**
     * Score specified document
     *
     * @param integer $docId
     * @param \Zend\Search\Lucene\SearchIndexInterface $reader
     * @return float
     */
    public function score($docId, Lucene\SearchIndexInterface $reader)
    {
        if (isset($this->_resVector[$docId])) {
            if ($this->_slop == 0) {
                $freq = $this->_exactPhraseFreq($docId);
            } else {
                $freq = $this->_sloppyPhraseFreq($docId, $reader);
            }

            if ($freq != 0) {
                $tf = $reader->getSimilarity()->tf($freq);
                $weight = $this->_weight->getValue();
                $norm = $reader->norm($docId, reset($this->_terms)->field);

                return $tf * $weight * $norm * $this->getBoost();
            }

            // Included in result, but culculated freq is zero
            return 0;
        } else {
            return 0;
        }
    }

    /**
     * Return query terms
     *
     * @return array
     */
    public function getQueryTerms()
    {
        return $this->_terms;
    }

    /**
     * Query specific matches highlighting
     *
     * @param Highlighter $highlighter  Highlighter object (also contains doc for highlighting)
     */
    protected function _highlightMatches(Highlighter $highlighter)
    {
        $words = array();
        foreach ($this->_terms as $term) {
            $words[] = $term->text;
        }

        $highlighter->highlight($words);
    }

    /**
     * Print a query
     *
     * @return string
     */
    public function __toString()
    {
        // It's used only for query visualisation, so we don't care about characters escaping
        if (isset($this->_terms[0]) && $this->_terms[0]->field !== null) {
            $query = $this->_terms[0]->field . ':';
        } else {
            $query = '';
        }

        $query .= '"';

        foreach ($this->_terms as $id => $term) {
            if ($id != 0) {
                $query .= ' ';
            }
            $query .= $term->text;
        }

        $query .= '"';

        if ($this->_slop != 0) {
            $query .= '~' . $this->_slop;
        }

        if ($this->getBoost() != 1) {
            $query .= '^' . round($this->getBoost(), 4);
        }

        return $query;
    }
}
