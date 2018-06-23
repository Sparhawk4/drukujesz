//Let's try the declarative pipeline
branches=["master"]
if (env.BRANCH_NAME in branches){
  node {
      stage ('init') {
          echo "Download repo"
          checkout scm
          sh "ls -al"    
          
      }
      stage ('stop containers'){
          def script = """\
            docker-compose stop
            docker-compose down""".stripIndent()
          sh "$script"
      }
      stage ('start new containers'){
          def script = "docker-compose -f docker-compose.stage.yaml up -d --force-recreate --build"
          sh "$script"
      }
  }
}
