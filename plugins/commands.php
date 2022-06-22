<?php

# Ignore inline messages (via @)
if ($v->via_bot) die;

# Start FortniteTracker class
$fn = new FortniteTracker($db);

# Private chat with Bot
if ($v->chat_type == 'private' || $v->inline_message_id) {
	if ($bot->configs['database']['status'] && $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	
	# Edit message by inline messages
	if ($v->inline_message_id) {
		$v->message_id = $v->inline_message_id;
		$v->chat_id = 0;
	}
	# Test API
	if ($v->command == 'test' and $v->isAdmin()) {
		$t = $bot->code(substr(json_encode($fn->getPlayer('Ship'), JSON_PRETTY_PRINT), 0, 4096));
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Start message
	elseif (in_array($v->command, ['start', 'start inline']) || $v->query_data == 'start') {
		$t = $bot->bold('🕹 Fortnite Stats') . PHP_EOL . $bot->italic($tr->getTranslation('startMessage'), 1);
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('searchButton'), ' ', 'switch_inline_query_current_chat');
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('aboutButton'), 'about'),
			$bot->createInlineButton($tr->getTranslation('helpButton'), 'help')
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'lang');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Help message
	elseif ($v->command == 'help' || $v->query_data == 'help') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('helpMessage');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# About message
	elseif ($v->command == 'about' || $v->query_data == 'about') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('aboutMessage', [explode('-', phpversion(), 2)[0]]);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Change language
	elseif ($v->command == 'lang' || $v->query_data == 'lang' || strpos($v->query_data, 'changeLanguage-') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'fr' => '🇫🇷 Français',
			'id' => '🇮🇩 Indonesia',
			'ir' => '🇮🇷 فارسی',
			'it' => '🇮🇹 Italiano',
			'ru' => '🇷🇺 pусский'
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($select);
				$user['lang'] = $select;
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$t = '🔡 Select your language';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} 
	# Search player
	else {
		if (!$v->query_data && !$v->command) {
			$data = $fn->getPlayer($v->text);
			if ($data['epicUserHandle']) {
				$wintot = $data['stats']['p2']['top1']['valueInt'] + $data['stats']['p10']['top1']['valueInt'] + $data['stats']['p9']['top1']['valueInt'];
				$killtot = $data['stats']['p2']['kills']['valueInt'] + $data['stats']['p10']['kills']['valueInt'] + $data['stats']['p9']['kills']['valueInt'];
				$args = [
					$data['epicUserHandle'],
					$data['accountId'], 
					$data['platformNameLong'], 
					round($data['stats']['p2']['top1']['valueInt']), 
					round($data['stats']['p10']['top1']['valueInt']), 
					round($data['stats']['p9']['top1']['valueInt']), 
					round($wintot), 
					round($data['stats']['p2']['kills']['valueInt']),
					round($data['stats']['p10']['kills']['valueInt']),
					round($data['stats']['p9']['kills']['valueInt']),
					$killtot, 
					round($data['stats']['p2']['winRatio']['valueInt']) . "%", 
					round($data['stats']['p10']['winRatio']['valueInt']) . "%", 
					round($data['stats']['p9']['winRatio']['valueInt']) . "%"
				];
				$t = $tr->getTranslation('playerStats', $args);
			} else {
				$t = $tr->getTranslation('playerNotFound');
			}
			if ($v->query_id) {
				$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
				$bot->answerCBQ($v->query_id);
			} else {
				$bot->sendMessage($v->chat_id, $t, $buttons, 'def', 0, 0);
			}
		} else {
			$t = $tr->getTranslation('unknownCommand');
			if ($v->query_id) {
				$bot->answerCBQ($v->query_id, $t);
			} else {
				$bot->sendMessage($v->chat_id, $t);
			}
		}
	}
} 
# Unsupported chats (Auto-leave)
elseif (in_array($v->chat_type, ['group', 'supergroup', 'channels'])) {
	$bot->leave($v->chat_id);
	die;
}

# Inline commands
if ($v->update['inline_query']) {
	$sw_text = $tr->getTranslation('helpInline');
	$sw_arg = 'inline'; // The message the bot receive is '/start inline'
	$results = [];
	# Search players with inline mode
	if ($v->query) {
		$data = $fn->getPlayer($v->query);
		if ($data['epicUserHandle']) {
			$wintot = $data['stats']['p2']['top1']['valueInt'] + $data['stats']['p10']['top1']['valueInt'] + $data['stats']['p9']['top1']['valueInt'];
			$killtot = $data['stats']['p2']['kills']['valueInt'] + $data['stats']['p10']['kills']['valueInt'] + $data['stats']['p9']['kills']['valueInt'];
			$args = [
				$data['epicUserHandle'],
				$data['accountId'], 
				$data['platformNameLong'], 
				round($data['stats']['p2']['top1']['valueInt']), 
				round($data['stats']['p10']['top1']['valueInt']), 
				round($data['stats']['p9']['top1']['valueInt']), 
				round($wintot), 
				round($data['stats']['p2']['kills']['valueInt']),
				round($data['stats']['p10']['kills']['valueInt']),
				round($data['stats']['p9']['kills']['valueInt']),
				$killtot, 
				round($data['stats']['p2']['winRatio']['valueInt']) . "%", 
				round($data['stats']['p10']['winRatio']['valueInt']) . "%", 
				round($data['stats']['p9']['winRatio']['valueInt']) . "%"
			];
			$t = $tr->getTranslation('playerStats', $args);
			$results[] = $bot->createInlineArticle(
				$v->query,
				$data['epicUserHandle'],
				$data['accountId'],
				$bot->createTextInput($t, 'def', 0)
			);
		} else {
			$sw_text = $tr->getTranslation('playerNotFound');
		}
	}
	$bot->answerIQ($v->id, $results, $sw_text, $sw_arg);
}

?>