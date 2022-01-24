<?php

namespace MediaWiki\Extension\FileExporter\Tests;

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
 *
 * @license GPL-2.0-or-later
 */
class FileExporterHooksTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
	}

	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_isNewbie() {
		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );
		$this->getExistingTestPage( $title );

		$skinTemplate = $this->createSkinTemplate(
			[],
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
			[],
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
	 * @covers ::getExportButtonLabel
	 */
	public function testOnSkinTemplateNavigation_success( $legacyConfig ) {
		$this->setMwGlobals( $legacyConfig );

		$title = Title::makeTitle( NS_FILE, __CLASS__ . mt_rand() );

		$skinTemplate = $this->createSkinTemplate(
			[
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
		$this->assertSame( $expectedUrl, $links['views']['fileExporter']['href'] );
		$this->assertSame( '(fileexporter-text)', $links['views']['fileExporter']['text'] );
	}

	/**
	 * @param array $config
	 * @param User $user
	 * @param Title|null $title
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
