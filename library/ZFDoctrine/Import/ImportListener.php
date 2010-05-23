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
 * Import Listener
 *
 * @author Benjamin Eberlei (kontakt@beberlei.de)
 */
interface ZFDoctrine_Import_ImportListener
{
    /**
     * @param string $className
     * @param string $moduleName
     */
    public function notifyRecordBuilt($className, $moduleName);

    /**
     * @return void
     */
    public function notifyImportCompleted();
}