# Changes


## 1.7.2

ClassLoader:

- Always cast psr0 directory to array when iterating the directory list.

## 1.7.0

HttpRequest:

- Added request body related methods: `getRequestBody()`, `openRequestBody()`, `closeRequestBody()`.
- Added `getRequestMethod()` method

## 1.6.0

- Added more request parameters
- Added ::createFromGlobals factory method to HttpRequest

## 1.5.0

- Added Psr4ClassLoader.
- Added Psr0ClassLoader.
- Added ChainedClassLoader.
- Added MapClassLoader.
- Added ClassLoader interface.

## 1.4.0

- Rewrite UploadedFile class for handling file upload operation.

## 1.3.0

- Improved ObjectContainer

## 0.0.8
- Add \Universal\ClassLoader\BasePathClassLoader
- Add \Universal\Requirement\Requirement class
