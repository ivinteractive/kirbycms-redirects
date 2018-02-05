<?php

// Link updating functionality through a custom route
if($user = site()->user()):
	$auth = r($user->hasPanelAccess(), true, false);
else:
	$auth = false;
endif;

if(!c::get('redirecty-template'))
	$kirby->set('blueprint', 'redirects', __DIR__ . DS . 'assets' . DS . 'blueprints' . DS . 'redirects.yaml');
if(c::get('redirecty-widget', true))
	$kirby->set('widget', 'redirecty', __DIR__ . DS . 'widgets' . DS . 'redirecty');

if(c::get('redirecty') && ($auth || c::get('redirecty-noauth',false))):

	require_once(__DIR__ . DS . 'lib' . DS . 'redirectyFunctions.php');

	$redirectsURI = c::get('redirects-list-uri', 'redirects');
	$dryrun = c::get('redirecty-dryrun', true);
	$multilang = c::get('languages', false);
	$case = c::get('redirecty-case', true);
	
	kirby()->routes(array(
		array(
			'pattern' => c::get('redirecty-uri', 'redirecty'),
			'action'	=> function() use($redirectsURI, $dryrun, $multilang, $case) {


				$response = '<html>';
				$response.= '<head>';
				$response.= css('https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700');
				$response.= css('https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
				$response.= '<style>'.file_get_contents(__DIR__ . DS . 'assets' . DS . 'css' . DS . 'redirecty.css').'</style>';
				$response.= '</head>';
				$response.= '<body>';

				try {

					$num = 0;

					$pages = site()->index()->not($redirectsURI);

					if(!page($redirectsURI))
						throw new Exception('No page found at the provided URI. Check c::set(\'redirects-list-uri\') in your config file, or create a page to hold your redirects.');

					if(page($redirectsURI)->redirects()->isEmpty())
						throw new Exception('Not able to find a redirects list at the given URI. Check c::set(\'redirects-list-uri\') in your config file, or add redirects to your list.');
					
					$redirects = page($redirectsURI)->redirects()->toStructure();

					$changeList = [];

					foreach($pages as $page):
						$changeList[$page->uri()] = false;
					endforeach;

					$init = microtime(true);

					foreach($redirects as $redirect):

						$pages = site()->search($redirect->old())->not($redirectsURI);

						if(!$result = updatePage($pages, $redirect, $num, $changeList, $multilang, $dryrun, $case))
							throw new Exception('Wasn\'t able to make updates for the old URI "'.$redirect.'". Please check your config variables to make sure they\'re set correctly.');
						
						$response.= $result['response'];
						$num = $result['num'];
						$changeList = $result['changeList'];

					endforeach;

					$pageNum = 0;
					foreach($changeList as $uri => $changed):
						if($changed)
							$pageNum++;
					endforeach;

					$subhead = '';

					if($pageNum > $num)
						$protip = '<h3><strong>Protip:</strong> Redirecty found old URIs in pages but didn\'t replace them. Set c::set(\'redirecty-case\', false) to search and replace without case sensitivity.</h3>';

					if($dryrun):
						$subhead.= '<h2>c::set(\'redirecty-dryrun\', false) to do it for real.</h2>';
						if(isset($protip))
							$subhead .= $protip;
						$response = '<h1>Redirecty would have made ' . $num . ' replacement'.r($num!==1,'s').' in ' . $pageNum . ' page'.r($pageNum!==1,'s').'.</h1>'.$subhead.'<ul>' . $response . '</ul>';
					else:
						$response = '<h1>Redirecty made ' . $num . ' replacement'.r($num!==1,'s').' in ' . $pageNum . ' page'.r($pageNum!==1,'s').'.</h1>'.((isset($protip)) ? $subhead.$protip : '').'<ul>' . $response . '</ul>';
					endif;

					$response.= '<p>Ran in  '. number_format((microtime(true) - $init), 6) . 's.</p>';

				} catch (Exception $e) {
					$response.= '<i class="fa fa-frown-o error"></i>';
					$response.= '<h1>There was an error.</h1>';
					$response.= '<p>'.$e->getMessage().'</p>';
				}

				$CSV = kirbytag(array(
					'link' => c::get('redirecty-csv','redirecty-csv'),
					'text' => 'CSV'
				));
				$JSON = kirbytag(array(
					'link' => c::get('redirecty-json','redirecty-json'),
					'text' => 'JSON'
				));
				$response.= '<download>Export the redirects list: '.$CSV.' / '.$JSON.'</download>';

				$response.= '</body>';
				$response.= '</html>';

				return new Response($response);

			}
		)
	));


	// Routing for CSV redirects exports
	kirby()->routes(array(
		array(
			'pattern' => c::get('redirecty-csv','redirecty-csv'),
			'action'	=> function() {
				$content = exportRedirects('csv');
				header::contentType('text/csv');
				header::download(array('name'=>'redirects.csv'));
				return new Response($content,'csv');
			}
		)
	));


	// Routing for JSON redirects exports
	kirby()->routes(array(
		array(
			'pattern' => c::get('redirecty-json','redirecty-json'),
			'action'	=> function() {
				$content = exportRedirects('json');
				header::contentType('application/json');
				header::download(array('name'=>'redirects.json'));
				return new Response($content,'json');
			}
		)
	));

endif;


// Functionality to import CSVs that have been uploaded to the page
kirby()->hook('panel.file.upload', function($file) {

	$page = $file->page();

	if($page->intendedTemplate()==c::get('redirecty-template','redirects')) {

		if($file->extension()=='csv' || $file->extension()=='json'):
			require_once(__DIR__ . DS . 'lib' . DS . 'redirectyFunctions.php');
			importRedirects($file);
			if(!c::get('redirecty-import-save', false)):
				$file->delete();
			endif;

		endif;
		
	}

});


// Redirect URIs set up in the redirects file
function redirecty() {

	$redirectsURI = c::get('redirecty-list', 'redirects');

    if($pg = page($redirectsURI)):
      $redirects = $pg->redirects()->toStructure();
    else:
      $redirects = new Collection([]);
    endif;

	$caseSensitive = c::get('redirecty-case', true);
	$baseRewrite = c::get('redirecty-subfolder', false);
	$redirectType = 301;

	// Remove the base subfolder from the path so we can match the path
	if($baseRewrite):
		$base = str_replace(url::base().'/','',url::index());
		$cleanPath = str_replace($base.'/','',url::path());
	else:
		$cleanPath = url::path();
	endif;

	$site = site();
	$language = $site->detectedLanguage();
	$langSwitch = r($language!=$site->defaultLanguage() && c::get('language.detect'), true, false);
	$langPath = ($langSwitch) ? $language->url : '';

	$uri = r($caseSensitive, $cleanPath, str::lower($cleanPath));

	if(c::get('redirecty-home', true) && page($uri) && page($uri)->isHomePage())
		header::redirect(url());

	$self = c::get('redirecty-self', url());

	if($self && $uri==$redirectsURI)
		header::redirect($self);

	foreach($redirects as $redirect):

		$match = r($caseSensitive, $uri==$redirect->old(), $uri==str::lower($redirect->old()));

		if($match):
			if($redirect->external()->isTrue()):
				header::redirect($redirect->new());
			else:
				if($langSwitch && page($redirect->new())):
					// Get the correct URI for the page, if the URL Key has been set
					$langURI = page($redirect->new())->uri($language->code);
					$url = $langPath.r(str::startsWith($redirect->new(),'/'), $langURI, '/'.$langURI);
					$redirectType = c::get('redirecty-multi', 302);
				else:
					// If a redirect is to the homepage (/), make sure we're not doubling up on forward slashes
					$url = r(str::startsWith($redirect->new(),'/'), $redirect->new(), '/'.$redirect->new());
				endif;
			
				// Add the base subfolder back in
				if($baseRewrite)
					$url = url::index().$url;

				header::redirect($url, (($redirect->code()->isNotEmpty()) ? $redirect->code()->value() : $redirectType));
			endif;

			exit;
		endif;

	endforeach;

}