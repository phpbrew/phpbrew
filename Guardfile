group :tests do
  guard :shell do
    watch(%r{^src/(.+)\.sh$}) {|m| %x{vendor/shebang_unit/shebang_unit test/case/#{m[1]}_test.sh} }
    watch(%r{^test/case/(.+)$}) {|m| %x{vendor/shebang_unit/shebang_unit test/case/#{m[1]}} }
  end
end
