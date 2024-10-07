desc "Create a debian package from the binaries."
task :build_artifact do |task|

  calver_version = ENV['PIPELINE_VERSION'].nil? ? Time.now.strftime("%Y.%m.%d.%H%M%S") : ENV['PIPELINE_VERSION']
  git_short_ref  = `git rev-parse --short HEAD`.strip
  version        = ENV['ARTIFACT_VERSION'].nil? ? "#{calver_version}+sha.#{git_short_ref}" : ENV['ARTIFACT_VERSION']
  artifact_name  = 'uitdatabank-entry-api'
  vendor         = 'publiq VZW'
  maintainer     = 'Infra publiq <infra@publiq.be>'
  license        = 'Apache-2.0'
  description    = 'UiTdatabank backend'
  source         = 'https://github.com/cultuurnet/udb3-backend'
  build_url      = ENV['JOB_DISPLAY_URL'].nil? ? "" : ENV['JOB_DISPLAY_URL']

  FileUtils.mkdir_p('pkg')
  FileUtils.mkdir_p('cache')
  FileUtils.mkdir_p('session')
  FileUtils.touch('config.php')
  FileUtils.rm('log/.gitignore')
  FileUtils.rm('web/downloads/.gitignore')
  FileUtils.rm('web/uploads/.gitignore')

  system("fpm -s dir -t deb -n #{artifact_name} -v #{version} -a all -p pkg \
    -x '.git*' -x pkg -x lib -x Rakefile -x Gemfile -x Gemfile.lock \
    -x .bundle -x Jenkinsfile -x vendor/bundle \
    --prefix /var/www/udb3-backend \
    --config-files /var/www/udb3-backend/config.php \
    --deb-user www-data --deb-group www-data \
    --description '#{description}' --url '#{source}' --vendor '#{vendor}' \
    --license '#{license}' -m '#{maintainer}' \
    --deb-field 'Pipeline-Version: #{calver_version}' \
    --deb-field 'Git-Ref: #{git_short_ref}' \
    --deb-field 'Build-Url: #{build_url}' \
    ."
  ) or exit 1
end
