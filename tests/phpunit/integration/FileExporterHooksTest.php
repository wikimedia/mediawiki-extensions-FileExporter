<?php

namespace MediaWiki\Extension\FileExporter\Tests\Unit;

use ExtensionRegistry;
use FileExporter\FileExporterHooks;
use HashConfig;
use MediaWikiIntegrationTestCase;
use SkinTemplate;
use Title;
use User;

/**
 * @group Database
 * @coversDefaultClass \FileExporter\FileExporterHooks
 */
class FileExporterHooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_betaDisabled() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) ) {
			$this->markTestSkipped();
		}

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getConfig' )->willReturn( new HashConfig( [
			'FileExporterBetaFeature' => true,
		] ) );

		$user = $this->createMock( User::class );
		$user->method( 'getOption' )
			->with( $this->equalTo( 'fileexporter' ) )
			->willReturn( '0' );
		$skinTemplate->method( 'getUser' )->willReturn( $user );

		// Peeking at details rather than using output, to be sure we took the intended branch.
		$user->expects( $this->never() )->method( 'isNewbie' );

		$links = [];
		FileExporterHooks::onSkinTemplateNavigation( $skinTemplate, $links );
		$this->assertEmpty( $links );
	}

	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_isNewbie() {
		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getConfig' )->willReturn( new HashConfig( [
			'FileExporterBetaFeature' => false,
		] ) );

		$skinTemplate->method( 'getUser' )
			->willReturn( new User() );

		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );
		$this->getExistingTestPage( $title );
		$skinTemplate->method( 'getTitle' )
			->willReturn( $title );

		$links = [];
		FileExporterHooks::onSkinTemplateNavigation( $skinTemplate, $links );

		$this->assertEmpty( $links );
	}

	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_nonexistingPage() {
		$title = Title::makeTitle( NS_FILE, __METHOD__ . mt_rand() );
		$existingPage = $this->getNonexistingTestPage( $title );
		$mockSkinTemplate = $this->createMock( SkinTemplate::class );
		$mockSkinTemplate->method( 'getConfig' )->willReturn( new HashConfig( [
			'FileExporterBetaFeature' => true,
		] ) );
		$mockSkinTemplate->method( 'getTitle' )
			->willReturn( $existingPage->getTitle() );
		$mockSkinTemplate->method( 'getUser' )
			->willReturn( $this->getTestUser()->getUser() );
		$links = [
			'views' => [],
		];

		FileExporterHooks::onSkinTemplateNavigation( $mockSkinTemplate, $links );

		$this->assertArrayNotHasKey( 'fileExporter', $links['views'] );
	}

	public function provideOnSkinTemplateNavigation_success() {
		return [
			'https protocol' => [
				[
					'wgServer' => 'https://w.invalid',
				]
			],
			'Relative protocol' => [
				[
					'wgServer' => '//w.invalid',
				],
			],
			'No protocol' => [
				[
					'wgServer' => 'w.invalid',
				],
			],
		];
	}

	/**
	 * @dataProvider provideOnSkinTemplateNavigation_success
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_success( $legacyConfig ) {
		$this->setMwGlobals( $legacyConfig );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getConfig' )->willReturn( new HashConfig( [
			'FileExporterBetaFeature' => false,
			'FileExporterTarget' => 'https://commons.invalid/wiki/Special:ImportFile',
		] ) );
		$skinTemplate->method( 'getUser' )
			->willReturn( $this->getTestUser( 'autoconfirmed' )->getUser() );

		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );
		$this->getExistingTestPage( $title );
		$skinTemplate->method( 'getTitle' )
			->willReturn( $title );

		$links = [];
		FileExporterHooks::onSkinTemplateNavigation( $skinTemplate, $links );

		$this->assertNotEmpty( $links );
		$localFileUrl = $title->getFullURL( '', false, PROTO_CANONICAL );
		$this->assertStringStartsWith( 'http', $localFileUrl );
		$expectedUrl = 'https://commons.invalid/wiki/Special:ImportFile?' .
			'clientUrl=' . urlencode( $localFileUrl ) . '&' .
			'importSource=FileExporter';
		$this->assertEquals( $expectedUrl, $links['views']['fileExporter']['href'] );
	}

}
