<?php

// Loop through the returned pages to update the links
function updatePage($pages, $redirect, $num, $changeList, $multilang, $dryrun, $case) {

	$return = array(
		'response'   => '',
		'num'		 => $num,
		'changeList' => $changeList
	);
	$pageChange = false;

	foreach($pages as $page):

		if($multilang):

			foreach($multilang as $lang):

				// Don't do the default language yet - if you do the non-default language last, the title will get updated to that language's title
				if(isset($lang['default']) && $lang['default'])
					continue;

				$return = returnResponse($return, multiLangPage($page, $redirect, $return['num'], $return['changeList'], $lang, $dryrun, $case));

	        endforeach;

	        foreach($multilang as $lang):

	        	// Do the default language now
	        	if($lang['default']):
					$return = returnResponse($return, multiLangPage($page, $redirect, $return['num'], $return['changeList'], $lang, $dryrun, $case));
	        		break;

	        	else:
	        		continue;

	        	endif;

	        endforeach;

		else:

			$return = returnResponse($return, singleLangPage($page, $redirect, $return['num'], $return['changeList'], $dryrun, $case));

		endif;

	endforeach;

	return $return;

}

// Update the variables we're keeping track of throughout the loop
function returnResponse($return, $result) {

	$old = $return['num'];
	$new = $result['num'];

	$response = $return['response'] . $result['response'];
	$num = $new;
	$changeList = a::merge($return['changeList'], $result['changeList']);

	return array(
		'response' => $response,
		'num' => $num,
		'changeList' => $changeList
	);

}

// Make sure that when we update the page, we're doing it with the correct language (files ending in .en.txt, .de.txt, etc)
function multiLangPage($page, $redirect, $num, $changeList, $lang, $dryrun, $case) {

	$response = '';

	$code = $lang['code'];

	$site = site();
	$site->language = new Languages($site);
	$site->language->code = $code;

	// Don't try to search a page that there isn't a language-variant for - that would autogenerate a new text file for that language
	if(f::exists($page->textfile())):

		foreach($page->content($code)->toArray() as $field => $value):

			$result = redirectReplace($page, $redirect->old(), $redirect->new(), $field, $value, $num, $lang, $dryrun, $case);
			$response.= $result['response'];
			$num+= $result['num'];
			if($result['pageChange'])
				$changeList[$page->uri().'-'.$code] = true;

		endforeach;

	endif;

	return array(
		'response'	 => $response,
		'num'		 => $num,
		'changeList' => $changeList
	);

}

// When there's no multilanguage setup (files ending in just .txt)
function singleLangPage($page, $redirect, $num, $changeList, $dryrun, $case) {

	$response = '';

	foreach($page->content()->toArray() as $field => $value):

		$result = redirectReplace($page, $redirect->old(), $redirect->new(), $field, $value, $num, false, $dryrun, $case);
		$response.= $result['response'];
		$num+= $result['num'];
		if($result['pageChange'])
			$changeList[$page->uri()] = true;

	endforeach;

	return array(
		'response'	 => $response,
		'num'		 => $num,
		'changeList' => $changeList
	);

}

// Add redirect matches to the response and update the page (if not a dryrun)
function redirectReplace($page, $old, $new, $field, $value, $num, $lang=false, $dryrun, $case) {

	$pageChange = false;
	$response = '';

	// Set whether it's a case-sensitive switch or not
	if($case):
		$contains = true;
		$replace = 'str_replace';
	else:
		$contains = false;
		$replace = 'str_ireplace';
		$value = str::lower($value);
		$old = str::lower($old);
	endif;

	if(str::contains($value, $old, $contains)):

		$strCount = substr_count($value, $old);
		$newValue = $replace($old, $new, $value);

		$formatted = '<li><strong><a href="'.$page->url().'" target="_blank">'.$page->uri().'</a></strong>'.r($lang,' - '.$lang['name']).'<br />';
		$formatted.= 'Field: '.$field.'<br />';
		$formatted.= $replace($old, '<strong>['.$old . ' <i class="fa fa-long-arrow-right"></i> ' . $new . ']</strong>', strip_tags($value));
		$formatted.= '<br /><br /></li>';

		$response.= $formatted;

		if(!$dryrun):
			try {
				if(!$page->update(array(
					$field => $newValue
				)))
					throw new Exception('Wasn\'t able to update the page <i class="fa fa-frown-o"></i>.');
			} catch (Exception $e) {
				$response.= $e->getMessage();
			}

		endif;

		$num += $strCount;
		$pageChange = true;

	endif;

	if(!isset($strCount))
		$strCount = 0;

	return array(
		'response' => $response,
		'num'	   => $strCount,
		'pageChange' => $pageChange
	);

}

function importRedirects($file) {

	$page = $file->page();

	// Default behavior is to just append the imported redirects
	$redirects = r(c::get('redirecty-import')=='replace', array(), $page->redirects()->yaml());


	if($file->extension()=='csv'):
		$file = file($file->root());

		foreach ($file as $line):
		    $item = str_getcsv($line);
		    $redirects[] = array(
		    	'old' => $item[0],
		    	'new' => $item[1],
		    	'external' => r(isset($item[2]) && $item[2]==1, true, false)
		    );
		endforeach;

	elseif($file->extension()=='json'):
		$file = json_decode($file->read());

		foreach ($file as $item):
			if(gettype($item)=='object'):
			    $redirects[] = array(
			    	'old' => $item->old,
			    	'new' => $item->new,
			    	'external' => r($item->external==true, true, false)
			    );
			elseif(gettype($item)=='array'):
				$redirects[] = array(
			    	'old' => $item[0],
			    	'new' => $item[1],
			    	'external' => r(isset($item[2]) && $item[2]==true, true, false)
			    );
			endif;
		endforeach;

	else:
		return false;

	endif;

	$page->update(array(
		'redirects' => yaml::encode($redirects)
	));

};

// Export the current redirects list as a CSV/JSON file
function exportRedirects($type) {

	$redirects = page(c::get('redirects-list-uri', 'redirects'))->redirects()->yaml();

	$content = '';

	if($type=='csv'):
		ob_start();
		$file = fopen('php://output', 'w');

		foreach($redirects as $redirect):
			fputcsv($file, $redirect);
		endforeach;

		fclose($file);

		$content = ob_get_clean();

	elseif($type=='json'):
		$content = json_encode($redirects);

	endif;

	return $content;

}