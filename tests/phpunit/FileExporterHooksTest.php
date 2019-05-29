<?php

namespace FileExporter\Tests;

use FileExporter\FileExporterHooks;
use MediaWikiTestCase;
use SkinTemplate;
use Title;

/**
 * @group Database
 * @coversDefaultClass \FileExporter\FileExporterHooks
 */
class FileExporterHooksTest extends MediaWikiTestCase {

	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_canExport() {
		$title = Title::makeTitle( NS_FILE, __METHOD__ . mt_rand() );
		$existingPage = $this->getExistingTestPage( $title );
		$mockSkinTemplate = $this->createMock( SkinTemplate::class );
		$mockSkinTemplate->method( 'getTitle' )
			->willReturn( $existingPage->getTitle() );
		$mockSkinTemplate->method( 'getUser' )
			->willReturn( $this->getTestUser()->getUser() );
		$links = [
			'views' => [],
		];

		FileExporterHooks::onSkinTemplateNavigation( $mockSkinTemplate, $links );

		$this->assertArrayHasKey( 'fileExporter', $links['views'] );
	}

	/**
	 * @covers ::onSkinTemplateNavigation
	 */
	public function testOnSkinTemplateNavigation_nonexistingPage() {
		$title = Title::makeTitle( NS_FILE, __METHOD__ . mt_rand() );
		$existingPage = $this->getNonexistingTestPage( $title );
		$mockSkinTemplate = $this->createMock( SkinTemplate::class );
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

}
