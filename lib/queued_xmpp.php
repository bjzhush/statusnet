<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Queue-mediated proxy class for outgoing XMPP messages.
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Network
 * @package   StatusNet
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR . '/lib/jabber.php';

class Queued_XMPP extends XMPPHP_XMPP
{
	/**
	 * Constructor
	 *
	 * @param string  $host
	 * @param integer $port
	 * @param string  $user
	 * @param string  $password
	 * @param string  $resource
	 * @param string  $server
	 * @param boolean $printlog
	 * @param string  $loglevel
	 */
	public function __construct($host, $port, $user, $password, $resource, $server = null, $printlog = false, $loglevel = null)
	{
        parent::__construct($host, $port, $user, $password, $resource, $server, $printlog, $loglevel);

        // We use $host to connect, but $server to build JIDs if specified.
        // This seems to fix an upstream bug where $host was used to build
        // $this->basejid, never seen since it isn't actually used in the base
        // classes.
        if (!$server) {
            $server = $this->host;
        }
        $this->basejid = $this->user . '@' . $server;

        // Normally the fulljid is filled out by the server at resource binding
        // time, but we need to do it since we're not talking to a real server.
        $this->fulljid = "{$this->basejid}/{$this->resource}";
    }

    /**
     * Send a formatted message to the outgoing queue for later forwarding
     * to a real XMPP connection.
     *
     * @param string $msg
     */
    public function send($msg, $timeout=NULL)
    {
        $qm = QueueManager::get('xmppout');
        $qm->enqueue(strval($msg), 'xmppout');
    }

    /**
     * Since we'll be getting input through a queue system's run loop,
     * we'll process one standalone message at a time rather than our
     * own XMPP message pump.
     *
     * @param string $message
     */
    public function processMessage($message) {
       $frame = array_shift($this->frames);
       xml_parse($this->parser, $frame->body, false);
    }

    //@{
    /**
     * Stream i/o functions disabled; push input through processMessage()
     */
    public function connect($timeout = 30, $persistent = false, $sendinit = true)
    {
        throw new Exception("Can't connect to server from XMPP queue proxy.");
    }

    public function disconnect()
    {
        throw new Exception("Can't connect to server from XMPP queue proxy.");
    }

    public function process()
    {
        throw new Exception("Can't read stream from XMPP queue proxy.");
    }

    public function processUntil($event, $timeout=-1)
    {
        throw new Exception("Can't read stream from XMPP queue proxy.");
    }

    public function read()
    {
        throw new Exception("Can't read stream from XMPP queue proxy.");
    }

    public function readyToProcess()
    {
        throw new Exception("Can't read stream from XMPP queue proxy.");
    }
    //@}
}

