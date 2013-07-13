<?php
	require( __DIR__.'/SplClassLoader.php' );
	$loader = new SplClassLoader( 'Instagram', dirname( __FILE__ ));
	$loader->register();

	$admin_url = $_GET['url'];

	// Authorization configuration
	$auth_config = array(
		'client_id'         => 'fcb5ed908edc462e95875eae35fd07f6',
		'client_secret'     => '660b7405f7d44055bf0eb0f1c821d331',
		'redirect_uri'      => "http://presstagram.appspot.com/pressta_auth?url=$admin_url",
		'scope'             => array( 'basic' )
	);

	$auth = new Instagram\Auth( $auth_config );
	$auth->authorize();
?>
