<?php

namespace MediaWiki\Extension\FileExporter\Tests\Unit;

use ExtensionRegistry;
use FileExporter\FileExporterHooks;
use HashConfig;
use IContextSource;
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

		$user = $this->createMock( User::class );
		$user->method( 'getOption' )
			->with( 'fileexporter' )
			->willReturn( '0' );

		$skinTemplate = $this->createSkinTemplate(
			[
				'FileExporterBetaFeature' => true,
			],
			$user
		);

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
		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );
		$this->getExistingTestPage( $title );

		$skinTemplate = $this->createSkinTemplate(
			[
				'FileExporterBetaFeature' => false,
			],
			new User(),
			$title
		);

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

		$mockSkinTemplate = $this->createSkinTemplate(
			[
				'FileExporterBetaFeature' => true,
			],
			$this->getTestUser()->getUser(),
			$existingPage->getTitle()
		);

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

		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );

		$skinTemplate = $this->createSkinTemplate(
			[
				'FileExporterBetaFeature' => false,
				'FileExporterTarget' => 'https://commons.invalid/wiki/Special:ImportFile',
			],
			$this->getTestUser( 'autoconfirmed' )->getUser(),
			$title
		);

		$this->getExistingTestPage( $title );

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

	/**
	 * @return SkinTemplate
	 */
	private function createSkinTemplate( array $config, User $user, Title $title = null ) {
		$context = $this->createMock( IContextSource::class );
		$context->method( 'getConfig' )->willReturn( new HashConfig( $config ) );
		$context->method( 'getUser' )->willReturn( $user );
		$context->method( 'getTitle' )->willReturn( $title );

		$skinTemplate = $this->createMock( SkinTemplate::class );
		$skinTemplate->method( 'getContext' )->willReturn( $context );
		return $skinTemplate;
	}

}
