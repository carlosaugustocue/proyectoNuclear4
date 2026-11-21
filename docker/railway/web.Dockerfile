# PWA compilada y servida por nginx (ADR-0006, ADR-0013).
# VITE_API_URL debe apuntar a la URL pública de la API, p. ej. https://api-xxxx.up.railway.app/v1
FROM node:22-alpine AS build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL
RUN npm test && npm run build

# Rama estable de nginx; apk upgrade aplica los parches de Alpine publicados después de la imagen base.
# Trivy bloquea el pipeline si queda alguna CVE CRITICAL corregible (ADR-0021, M-20).
FROM nginx:1.30-alpine
RUN apk upgrade --no-cache
COPY docker/railway/web-nginx.conf /etc/nginx/templates/default.conf.template
COPY --from=build /app/dist /usr/share/nginx/html
ENV PORT=8080
EXPOSE 8080
