import { defineRailway, image, project, service, volume } from "railway/iac";

export default defineRailway(() => {
  const postgresVolume = volume("postgres-volume", { alerts: { usage: { "100": {}, "80": {}, "95": {} } }, allowOnlineResize: true, region: "us-east4-eqdc4a", sizeMB: 5000 });
  const worker = service("worker", {
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "docker/railway/api.Dockerfile" },
    deploy: { startCommand: "php artisan queue:work --queue=domain-events --tries=3", restartPolicyType: "ON_FAILURE", restartPolicyMaxRetries: 5 },
    replicas: { "us-east4-eqdc4a": 1 },
  });
  const web = service("web", {
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "docker/railway/web.Dockerfile" },
    replicas: { "us-east4-eqdc4a": 1 },
  });
  const postgres = service("postgres", {
    source: image("postgis/postgis:16-3.4"),
    build: { buildEnvironment: "V3", builder: "RAILPACK" },
    replicas: { "us-east4-eqdc4a": 1 },
    volumeMounts: { "/var/lib/postgresql/data": postgresVolume },
  });
  const api = service("api", {
    build: { buildEnvironment: "V3", builder: "DOCKERFILE", dockerfilePath: "docker/railway/api.Dockerfile" },
    deploy: { restartPolicyType: "ON_FAILURE", restartPolicyMaxRetries: 5 },
    replicas: { "us-east4-eqdc4a": 1 },
  });

  return project("precios-armenia", {
    variables: { managed: false },
    resources: [worker, web, postgres, api, postgresVolume],
  });
});
