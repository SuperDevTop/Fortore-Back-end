git pull
docker build . -f Dockerfile.Prod  -t ghcr.io/mhadiahmed/fortore_investment:latest
docker push ghcr.io/mhadiahmed/fortore_investment:latest
helm upgrade --install investment ./deploy/ -f env-values.yaml -n default
