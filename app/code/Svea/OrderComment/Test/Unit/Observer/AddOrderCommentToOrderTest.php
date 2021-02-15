<?php

namespace Svea\OrderComment\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Svea\OrderComment\Observer\AddOrderCommentToOrder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Svea\OrderComment\Model\Data\OrderComment;

class AddOrderCommentToOrderTest extends \PHPUnit\Framework\TestCase
{
    protected $objectManager;

    /**
     * @var AddOrderCommentToOrder
     */
    protected $observer;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $configMock = $this->getMockBuilder(\Svea\OrderComment\Model\OrderCommentConfigProvider::class)
             ->onlyMethods(['isEnabled'])
             ->disableOriginalConstructor()
             ->getMock();
        $configMock->expects(TestCase::any())
             ->method('isEnabled')
             ->willReturn(true);
        $this->observer = new AddOrderCommentToOrder($configMock);
    }
    
    public function testExecute()
    {
        $comment = 'test comment';

        $observerMock = $this->getMockBuilder('Magento\Framework\Event\Observer')
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder('Magento\Framework\Event')
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock = $this->getMockBuilder('Magento\Quote\Model\Quote')
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder('Magento\Sales\Model\Order')
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $map = [
            ['quote', null, $quoteMock],
            ['order', null, $orderMock]
        ];
        
        $observerMock->expects($this->atLeastCount(2))
            ->method('getEvent')
            ->willReturn($eventMock);
        $eventMock->expects($this->atLeastCount(2))
            ->method('getData')
            ->willReturnMap($map);

        $quoteMock->expects(TestCase::atLeastOnce())
            ->method('getData')
            ->with(OrderComment::COMMENT_FIELD_NAME)
            ->willReturn($comment);
        
        $this->observer->execute($observerMock);

        self::assertEquals($comment, $orderMock->getData(OrderComment::COMMENT_FIELD_NAME));
    }

    public function atLeastCount($num)
    {
        return TestCase::atLeast($num);
    }
}
