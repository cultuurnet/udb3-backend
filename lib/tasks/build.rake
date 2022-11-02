desc "Build binaries"
task :build do |task|

  system('composer install --no-dev --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-interaction') or exit 1
end
