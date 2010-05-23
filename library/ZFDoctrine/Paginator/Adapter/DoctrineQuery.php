<?php
/**
 * ZFDoctrine
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

/**
 * description
 *
 * @author Benjamin Eberlei (kontakt@beberlei.de)
 * @author Juokaz
 */

class ZFDoctrine_Paginator_Adapter_DoctrineQuery implements Zend_Paginator_Adapter_Interface
{
    /**
     * @var Doctrine_Query
     */
    protected $_query;

    /**
     * @var int
     */
    protected $_rowCount;

    /**
     * @param Doctrine_Query $query
     * @param int $hydrationMode
     */
    public function __construct(Doctrine_Query $query, $hydrationMode = null)
    {
        $this->_query = $query;

        if ($hydrationMode !== null) {
            $this->_query->setHydrationMode($hydrationMode);
        }
    }

    /**
     * Get items
     *
     * @param int $offset
     * @param int $itemsPerPage
     * @return Doctrine_Collection
     */
    public function getItems($offset, $itemsPerPage)
    {
        if ($itemsPerPage !== null) {
            $this->_query->limit($itemsPerPage);
        }
        if ($offset !== null) {
            $this->_query->offset($offset);
        }

        return $this->_query->execute();
    }

    /**
     * Count results
     *
     * @return int
     */
    public function count()
    {
        if ($this->_rowCount === null) {
            $this->_rowCount = $this->_query->count();
        }

        return $this->_rowCount;
    }

    /**
     * Set the row count
     *
     * @param int $rowCount
     */
    public function setRowCount($rowCount)
    {
        $this->_rowCount = $rowCount;
        return $this;
    }
}