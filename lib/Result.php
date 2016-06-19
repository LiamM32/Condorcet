<?php
/*
    Condorcet PHP Class, with Schulze Methods and others !

    By Julien Boudry - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace Condorcet;

use Condorcet\CondorcetException;
use Condorcet\CondorcetVersion;
use Condorcet\Candidate;
use Condorcet\Election;
use Condorcet\Linkable;


class Result implements \ArrayAccess, \Countable, \Iterator 
{
    use CondorcetVersion;

    // Implement Iterator

    function rewind() {
        reset($this->_UserResult);
    }

    function current () {
        return current($this->_UserResult);
    }

    function key () : int {
        return key($this->_UserResult);
    }

    function next () {
        next($this->_UserResult);
    }

    function valid () : bool {
        return (key($this->_UserResult) === null) ? false : true;
    }

    // Implement ArrayAccess

    public function offsetSet ($offset, $value) {
        throw new CondorcetException (0,"Can't change a result");
    }

    public function offsetExists ($offset) : bool {
        return isset($this->_UserResult[$offset]);
    }

    public function offsetUnset ($offset) {
        throw new CondorcetException (0,"Can't change a result");
    }

    public function offsetGet ($offset) {
        return isset($this->_UserResult[$offset]) ? $this->_UserResult[$offset] : null;
    }

    // Implement Countable

    public function count () : int {
        return count($this->_UserResult);
    }


    // Result

    protected $_Election;

    protected $_Result;
    protected $_UserResult;

    public function __construct (Election $election, array $result)
    {
        $this->_Election = $election;
        $this->_Result = $result;
        $this->_UserResult = $this->makeUserResult();
    }

    public function getResultAsArray (bool $convertToString = false) : array
    {
        $r = $this->_UserResult;

        foreach ($r as &$rank) :
            if (count($rank) === 1) :
                $rank = ($convertToString) ? (string) $rank[0] : $rank[0];
            elseif ($convertToString) :
                foreach ($rank as &$subRank) :
                    $subRank = (string) $subRank;
                endforeach;
            endif;
        endforeach;

        return $r;
    }

    public function getResultAsInternalKey () : array
    {
        return $this->_Result;
    }


    protected function makeUserResult () : array
    {
        $userResult = [];

        foreach ( $this->_Result as $key => $value ) :
            if (is_array($value)) :
                foreach ($value as $candidate_key) :
                    $userResult[$key][] = $this->_Election->getCandidateId($candidate_key);
                endforeach;
            elseif (is_null($value)) :
                $userResult[$key] = null;
            else :
                $userResult[$key][] = $this->_Election->getCandidateId($value);
            endif;
        endforeach;

        foreach ( $userResult as $key => $value ) :
            if (is_null($value)) :
                $userResult[$key] = null;
            endif;
        endforeach;

        return $userResult;
    }

}
