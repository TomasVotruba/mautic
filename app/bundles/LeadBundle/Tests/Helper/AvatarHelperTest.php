<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

final class AvatarHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&Lead
     */
    private MockObject $leadMock;

    private AvatarHelper $avatarHelper;

    protected function setUp(): void
    {
        $root = realpath(__DIR__.'/../../../../../');

        $assetsHelperMock = new AssetsHelper($this->createStub(Packages::class));
        $pathsHelperMock  = $this->createMock(PathsHelper::class);
        $pathsHelperMock->expects($this->once())->method('getSystemPath')
        ->willReturn('http://localhost');
        $pathsHelperMock->expects($this->once())->method('getAssetsPath')
          ->willReturn($root.'/app/assets');
        $pathsHelperMock->expects($this->once())->method('getMediaPath')
          ->willReturn($root.'/media');

        $assetsHelperMock->setPathsHelper($pathsHelperMock);
        $defaultAvatarHelperMock       = new DefaultAvatarHelper($assetsHelperMock);
        $gravatarHelperMock            = new GravatarHelper($defaultAvatarHelperMock, $this->createStub(CoreParametersHelper::class), $this->createStub(RequestStack::class));
        $this->leadMock                = $this->createMock(Lead::class);
        $this->avatarHelper            = new AvatarHelper($assetsHelperMock, $pathsHelperMock, $gravatarHelperMock, $defaultAvatarHelperMock);
    }

    /**
     * Test to get gravatar.
     */
    public function testGetAvatarWhenGravatar(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_PORT']     = '80';
        $_SERVER['SERVER_NAME']     = 'localhost';
        $_SERVER['REQUEST_URI']     = 'localhost';

        $this->leadMock->expects($this->once())->method('getPreferredProfileImage')
            ->willReturn('gravatar');
        $this->leadMock->expects($this->once())->method('getSocialCache')
            ->willReturn([]);
        $this->leadMock->expects($this->once())->method('getEmail')
            ->willReturn('mautic@acquia.com');
        $avatar = $this->avatarHelper->getAvatar($this->leadMock);
        $this->assertSame('https://www.gravatar.com/avatar/96f1b78c73c1ee806cf6a4168fe9bf77?s=250&d=http%3A%2F%2Flocalhost%2Fimages%2Favatar.png', $avatar, 'Gravatar image should be returned');

        unset($_SERVER['SERVER_PROTOCOL']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * Test to get default image.
     */
    public function testGetAvatarWhenDefault(): void
    {
        $this->leadMock->expects($this->once())->method('getPreferredProfileImage')
            ->willReturn('gravatar');
        $this->leadMock->expects($this->once())->method('getSocialCache')
            ->willReturn([]);
        $this->leadMock->expects($this->once())->method('getEmail')
            ->willReturn('');
        $avatar = $this->avatarHelper->getAvatar($this->leadMock);

        $this->assertSame('http://localhost/images/avatar.png', $avatar, 'Default image image should be returned');
    }
}
