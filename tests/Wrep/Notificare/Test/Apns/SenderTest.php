<?php

namespace Wrep\Notificare\Tests\Apns;

use \Wrep\Notificare\Apns\Sender;
use \Wrep\Notificare\Apns\Certificate;
use \Wrep\Notificare\Apns\Gateway;
use \Wrep\Notificare\Apns\MessageFactory;
use \Wrep\Notificare\Apns\MessageEnvelope;
use \Wrep\Notificare\Test\Apns\Mock\MockGatewayFactory;
use \Wrep\Notificare\Test\Apns\Mock\MockGateway;

class SenderTests extends \PHPUnit_Framework_TestCase
{
	private $sender;

	public function setUp()
	{
		// Get our sender
		$this->sender = new Sender();
		$this->sender->setGatewayFactory(new MockGatewayFactory());
	}

	private function getCertificate($fingerprint)
	{
		// Create cert
		$certificate = $this->getMockBuilder('\Wrep\Notificare\Apns\Certificate')
							->disableOriginalConstructor()
							->getMock();
		$certificate->expects($this->any())
					->method('getFingerprint')
					->will($this->returnValue($fingerprint));

		return $certificate;
	}

	public function testSend()
	{
		$messageFactory = new MessageFactory( $this->getCertificate('a') );
		$message = $messageFactory->createMessage('ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff');

		$this->assertEquals(0, $this->sender->getQueueLength());

		$messageEnvelope = $this->sender->send($message);

		$this->assertEquals(0, $this->sender->getQueueLength());
		$this->assertEquals(MessageEnvelope::STATUS_NOERRORS, $messageEnvelope->getStatus());
	}

	public function testQueueAndFlush()
	{
		$certificateA = $this->getCertificate('a');
		$certificateB = $this->getCertificate('b');
		$certificateC = $this->getCertificate('c');

		// Create messages
		$messageFactory = new MessageFactory($certificateA);
		$messageA = $messageFactory->createMessage('ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff');
		$messageB = $messageFactory->createMessage('ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', $certificateB);
		$messageC = $messageFactory->createMessage('ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', $certificateC);

		// Connect and queue the messages
		$sender = new Sender($certificateA);
		$sender->setGatewayFactory(new MockGatewayFactory());

		for ($i = 1; $i <= 5; $i++)
		{
			$sender->queue($messageA);
			$sender->queue($messageB);
			$sender->queue($messageC);
			$this->assertEquals($i * 3, $sender->getQueueLength());
		}

		// Send the messages
		$sender->flush();
		$this->assertEquals(0, $sender->getQueueLength());
	}
}