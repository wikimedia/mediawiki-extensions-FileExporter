<?php

namespace MoveToCommonsClient;

use Message;
use SkinTemplate;

/**
 * @author Addshore
 */
class MoveToCommonsClientHooks {

	public static function onSkinTemplateNavigation( SkinTemplate &$sktemplate, array &$links ) {
		global $wgMoveToCommonsClientTarget;

		if ( $sktemplate->getTitle()->getNamespace() !== NS_FILE ) {
			return;
		}

		$links['views']['moveToCommons'] = [
			'class' => '',
			'text' => Message::newFromKey( 'movetocommonsclient-text' )->plain(),
			'href' => $wgMoveToCommonsClientTarget,
		];
	}

}
