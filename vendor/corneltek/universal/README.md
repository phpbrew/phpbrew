Universal
=========

Universal is a general proprose PHP library.

[![Build Status](https://travis-ci.org/c9s/Universal.svg?branch=master)](https://travis-ci.org/c9s/Universal)

# Components

- ClassLoaders
- Container
- HTTPRequest

## Classloader

### SplClassLoader

    use Universal\ClassLoader\SplClassLoader;
    $loader = new \UniversalClassLoader\SplClassLoader( array(  
            'Vendor\Onion' => 'path/to/Onion',
            'Vendor\CLIFramework' => 'path/to/CLIFramework',
    ));
    $loader->addNamespace(array( 
        'NS' => 'path'
    ));
    $loader->useIncludePath();
    $loader->register();

### BasePathClassLoader

    $loader = new BasePathClassLoader( array( 
        'vendor/pear', 'external_vendor/src'
    ) );
    $loader->useEnvPhpLib();
    $loader->register();

### Include Path Manipulator

Include Path manipulator
 
    $includer = new PathIncluder(array( 'to/path', ... ));
    $includer->add( 'path/to/lib' );
    $includer->setup();   // write set_include_path

## Http

### StreamResponse

MXHR support

```php
    $response = new Universal\Http\StreamResponse;
    for( $i = 0 ; $i < 30000 ; $i++ ) {
        $response->write(json_encode(array('i' => $i)), array(
            'Content-Type' => 'application/json',
        ));
        usleep(200000);
    }
    $response->finish();
```

### HttpRequest

For multiple files:

```php
<?php

$req = new HttpRequest;
foreach( $req->files as $f ) {
    $extname = $f->getExtension();
    $filename = $f->getPathname();
}

$req->param( 'username' );   // get $_REQUEST['username'];

$req->get->username;    // get $_GET['username'];

$req->post->username;   // get $_POST['username'];

$req->server->path_info;  // get $_SERVER['path_info'];
```

To get FILE:

    $req = new HttpRequest;

Get $_FILES['uploaded'] hash:

    $req->files->uploaded;

Get file size:

    $req->files->uploaded->size;

Get file mime type:

    $req->files->uploaded->type; // plain/text

Get upload error:

    $req->files->uploaded->error;

Foreach file:

    foreach( $req->files->uploaded as $f ) {
        $f->size;
    }


## ObjectContainer

Construct a $container object or inherit from it:

    $container = new Universal\Container\ObjectContainer;

Register a object builder for lazy building.

    $container->mailer = function() {
        return new YourMailer;
    };

To get singleton object via `__get` magic method:

    $mailer = $container->mailer;

Or get singleton object from `instance` method:

    $mailer = $container->instance('mailer');

To build a new object:

    $mailer = $container->build('mailer');

To build a new object with arguments:

    $mailer = $container->build('mailer', array( ... ));

## Session

Supported Session Storage backend:

- Memcache
- Redis
- Native

use ObjectContainer to pass options:

    $container = new Universal\Container\ObjectContainer;
    $container->state = function() {
        return new Universal\Session\State\NativeState;
    };
    $container->storage = function() {
        return new Universal\Session\Storage\NativeStorage;
    };

Native Session:

    $session = new Universal\Session\Session(array(  
        'state'   => new Universal\Session\State\NativeState,
        'storage' => new Universal\Session\Storage\NativeStorage,
    ));
    $counter = $session->get( 'counter' );
    $session->set( 'counter' , ++$counter );
    echo $session->get( 'counter' );

Session with memcache backend:

    $session = new Universal\Session\Session(array(  
        'state'   => new Universal\Session\State\CookieState,
        'storage' => new Universal\Session\Storage\MemcacheStorage,
    ));
    $counter = $session->get( 'counter' );
    $session->set( 'counter' , ++$counter );
    echo $session->get( 'counter' );

## Event

    use Universal\Event\PhpEvent;
    $e = PhpEvent::getInstance();

    // register your handler
    $e->register( 'test', function($a,$b,$c) {
        // do what you want

    });

    // trigger event handlers
    $e->trigger( 'test' , 1,2,3  );

## Requirement Checker

    try {
        $require = new Universal\Requirement\Requirement;
        $require->extensions( 'apc','mbstring' );
        $require->classes( 'ClassName' , 'ClassName2' );
        $require->functions( 'func1' , 'func2' , 'function3' )
    }
    catch( RequireExtensionException $e ) {

    }
    catch( RequireFunctionException $e ) {

    }
    catch( RequireClassException $e ) {

    }
