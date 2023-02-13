pipeline {
  agent none
  environment {
    HOME="${WORKSPACE}"
  }
  options {
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '2'))
  }
  stages {
    stage("Lint & Test") {
      agent {
        docker {
          image 'php:8.0.1-cli'
        }
      }
      stages {
        stage("Lint") {
          steps {
            sh "sh phplint.sh ."
          }
        }
        /*stage("Test") {
          steps {
            sh ""
          }
        }*/
        stage("artifact") {
          when {
            branch pattern: "(master|develop|release.*)", comparator: "REGEXP"
          }
          steps {
            archiveArtifacts artifacts: '**/*', fingerprint: true
          }
        }
      }
    }
    stage("deploy on dev server") {
      when {
        branch 'develop'
      }
      agent {
        dockerfile true
      }
      steps {
        sh 'mkdir -p Ansible/deploy'
        sh 'for file in *;do test "$file" != "Ansible" && cp -r "$file" "Ansible/deploy/";done'
        withCredentials([file(credentialsId: 'projectvoice-dev-key', variable: 'KEYFILE')]) {
          sh """
              cd Ansible
              ansible-playbook backend-deploy.yml -i hosts.yml --extra-vars host=dev --extra-vars key='$KEYFILE'
          """
        }
      }
    }
    /*stage("CodeReview") {
      when {
        branch 'develop'
      }
      agent {
        docker {
          image 'sonarsource/sonar-scanner-cli'
          args '-v $HOME:/usr/src --entrypoint=""'
        }
      }
      steps {
        withCredentials([string(credentialsId: 'SonarQubeDriveItBackend, variable: 'TOKEN')]) {
          sh "sonar-scanner -Dsonar.projectKey=DriveItBackend -Dsonar.sources=. -Dsonar.host.url=http://116.203.245.202:9000 -Dsonar.login=$TOKEN"
        }
      }
    }*/
    stage("Clean up workspace") {
      when {
        not {
          branch pattern: "(master|develop|release.*)", comparator: "REGEXP"
        }
      }
      agent {
        dockerfile true
      }
      steps {
        deleteDir()
      }
    }
  }
}
