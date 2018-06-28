CHANGELOG
==================

## Version 2.7

- Added ability to make class final.
- if - else statement , should always contain else block even if no arg provided.
- forEach statement.
- Added AppClassGenerator.
- Added object property expression.

## Version 2.6

- Implemented try catch statement
- Added StaticMethodCall

## Version 2.5

- Added StaticMethodCall
- Added IfIssetStatement
- Added TryCatchStatement

## Version 2.4

- Added RequireClassStatement
- Added DefineStatement

## Version 2.2

- Added generatePsr0ClassUnder, generatePsr4ClassUnder methods for generating classes.

## Version 2.1
- Added RequireStatement
- Added RequireOnceStatement
- Added MethodCallStatement
- Added CallExpr

Version 2.0
-----------------

MethodCall

- `MethodCall` is now removed.
- Changed `MethodCall` default object name from `this` to `$this`. be sure to create MethodClass with '$this'
- Improve argument exporting.
- Renamed `MethodCall` to `MethodCall`

Statement

- `Statement` class now render the content with a comma at the end.

Line

- `Line` class was added to provide indentation operation.


Version 1.4.5
-----------------

- `Block::indent()` and `Block::unindent()` is now deprecated. use
  `increaseIndentLevel()` and `decreaseIndentLevel()` instead.
- `AllowIndent` and `AutoIndent` is now removed from Block class, it's now depends
   on the indent level.
- Added `BracketedBlock` to support bracket wrapped block.
- Improved indentation.
- Added Argument class
