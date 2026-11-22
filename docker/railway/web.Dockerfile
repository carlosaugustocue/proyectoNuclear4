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
# Origen de la API para la CSP de nginx (connect-src). Falla el build si VITE_API_URL no es una URL.
RUN node -e "process.stdout.write(new URL(process.env.VITE_API_URL || 'http://localhost:8080/v1').origin)" > /app/api-origen.txt

# Rama estable de nginx; apk upgrade aplica los parches de Alpine publicados después de la imagen base.
# Trivy bloquea el pipeline si queda alguna CVE CRITICAL corregible (ADR-0021, M-20).
FROM nginx:1.30-alpine
RUN apk upgrade --no-cache
COPY docker/railway/web-nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/railway/web-seguridad.conf /etc/nginx/snippets/seguridad.conf
COPY --from=build /app/api-origen.txt /tmp/api-origen.txt
RUN sed -i "s#__API_ORIGEN__#$(cat /tmp/api-origen.txt)#" /etc/nginx/snippets/seguridad.conf \
    && ! grep -q __API_ORIGEN__ /etc/nginx/snippets/seguridad.conf && rm /tmp/api-origen.txt
COPY --from=build /app/dist /usr/share/nginx/html
ENV PORT=8080
EXPOSE 8080
