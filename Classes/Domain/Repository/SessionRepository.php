<?php
namespace Bitmotion\SingleSignon\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Bitmotion\SingleSignon\Domain\Model\Session;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class SessionRepository
 */
class SessionRepository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_singlesignon_sessions';

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct(DatabaseConnection $databaseConnection = null)
    {
        $this->databaseConnection = $databaseConnection ?: $GLOBALS['TYPO3_DB'];
    }

    /**
     * Adds or updates the session table
     *
     * @param Session $session
     */
    public function addOrUpdateSession(Session $session)
    {
        $values = array();
        foreach ($session->getValues() as $name => $value) {
            $values[$name] = is_scalar($value) ? $value : serialize($value);
        }
        $insertQuery = $this->databaseConnection->INSERTquery($this->tableName, $values);
        $this->databaseConnection->sql_query($insertQuery . $this->getOnDuplicateKeyStatement($session));
    }

    /**
     * @param string $sessionId
     * @return array|NULL
     */
    public function findBySessionId($sessionId)
    {
        $activeSessions = $this->databaseConnection->exec_SELECTgetRows(
            '*',
            $this->tableName,
            'session_hash=' . $this->databaseConnection->fullQuoteStr($sessionId, $this->tableName)
        );

        return $activeSessions;
    }

    /**
     * @param string $sessionHash
     * @param string $userId
     * @param string $appId
     */
    public function deleteBySessionHashUserIdAppId($sessionHash, $userId, $appId)
    {
        $this->databaseConnection->exec_DELETEquery(
            $this->tableName,
            sprintf(
                'session_hash=%s AND user_id=%s AND app_id=%s',
                $this->databaseConnection->fullQuoteStr($sessionHash, $this->tableName),
                $this->databaseConnection->fullQuoteStr($userId, $this->tableName),
                $this->databaseConnection->fullQuoteStr($appId, $this->tableName)
            )
        );
    }

    /**
     * Removes the identifiers and adds ON DUPLICATE KEY statement for data values
     *
     * @param Session $session
     * @return string
     */
    protected function getOnDuplicateKeyStatement(Session $session)
    {
        $updateValues = array();
        foreach (array_slice($session->getValues(), 3) as $name => $value) {
            $updateValues[] = "$name=VALUES($name)";
        }
        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateValues);
    }
}
