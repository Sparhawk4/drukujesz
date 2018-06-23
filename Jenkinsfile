//Let's try the declarative pipeline
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
        def script = """\
          docker-compose -f docker-compose.stage.yaml up -d --force-recreate --build
          docker-compose down""".stripIndent()
        sh "$script"
    }
}
