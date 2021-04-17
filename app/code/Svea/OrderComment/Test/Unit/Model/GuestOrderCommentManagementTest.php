<?php

namespace Svea\OrderComment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Test\Unit\Model\GuestCart\GuestCartTestHelper;
use PHPUnit\Framework\TestCase;

class GuestOrderCommentManagementTest extends TestCase
{
    /**
     * @var \Svea\OrderComment\Model\GuestOrderCommentManagement
     */
    protected $testObject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteIdMaskFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteIdMaskMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderCommentManagementMock;

    /**
     * @var string
     */
    protected $maskedCartId;

    /**
     * @var int
     */
    protected $cartId;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        
        $this->quoteRepositoryMock = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
              ->disableOriginalConstructor()
              ->setMethods(['get'])
              ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getItemsCount',
                'save',
                '__wakeup'
            ])
            ->getMock();

        $this->orderCommentManagementMock
            = $this->getMockBuilder('\Svea\OrderComment\Model\OrderCommentManagement')
            ->disableOriginalConstructor()
            ->getMock();

        $this->maskedCartId = 'f216207248d65c789b17be8545e0aa73';
        $this->cartId = 123;

        $guestCartTestHelper = new GuestCartTestHelper($this);
        [$this->quoteIdMaskFactoryMock, $this->quoteIdMaskMock] = $guestCartTestHelper->mockQuoteIdMask(
            $this->maskedCartId,
            $this->cartId
        );

        $this->testObject = $objectManager->getObject(
            'Svea\OrderComment\Model\GuestOrderCommentManagement',
            [
                'orderCommentManagement' => $this->orderCommentManagementMock,
                'quoteIdMaskFactory' => $this->quoteIdMaskFactoryMock
            ]
        );
    }

    public function testSaveComment()
    {
        $comment = 'test comment';

        $orderCommentMock = $this->getMockBuilder('\Svea\OrderComment\Model\Data\OrderComment')
            ->disableOriginalConstructor()
            ->onlyMethods(['getComment'])
            ->getMock();
        
        $this->orderCommentManagementMock->expects(TestCase::once())
            ->method('saveOrderComment')
            ->with($this->cartId, $orderCommentMock)
            ->willReturn($comment);
        $result = $this->testObject->saveOrderComment($this->maskedCartId, $orderCommentMock);
        $this->assertEquals($comment, $result);
    }
}
