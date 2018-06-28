CHANGELOG
==================

Version 2.0.x
-----------------

MethodCallExpr

- `MethodCall` is now removed.
- Changed `MethodCallExpr` default object name from `this` to `$this`. be sure to create MethodClass with '$this'
- Improve argument exporting.
- Renamed `MethodCallExpr` to `MethodCallExpr`

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
