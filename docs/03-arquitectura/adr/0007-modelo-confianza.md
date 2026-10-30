# ADR-0007: Modelo de confianza de precios — marcar, no borrar

- **Estado:** Propuesto (umbrales por validar con datos de campo)
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-03, EC-02, preguntas de diseño 1, 2 y 3

## Contexto
Las observaciones provienen de reportantes ocasionales, frecuentes y, desde el corte 3, de comercios con interés propio. Un competidor puede intentar desprestigiar a otro con precios falsos. Al mismo tiempo, una tienda puede tener legítimamente un precio muy bajo (equidad entre comercios, guía §12).

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Moderación manual previa de todo reporte | Alta precisión | No escala; mata la frescura (EC-02) |
| Mostrar todo sin control | Simple | Incumple EC-03 |
| Aprendizaje automático | Potente | Sin datos etiquetados al inicio; poco explicable |
| **Reglas explicables: vigencia + corroboración + anomalía robusta + reputación + moderación posterior** | Explicable, probable con TDD, funciona con pocos datos | Requiere ajustar umbrales |

## Decisión
Cada observación tiene un **estado de confianza** (`CONFIABLE`, `POR_VERIFICAR`, `NO_CONFIABLE`) calculado por el módulo Confianza con las reglas RN-04 a RN-08 de la especificación (vigencia por categoría, corroboración, puntaje z modificado con MAD, peso por reputación y límites de aporte). Los reportes sospechosos **se marcan y se envían a moderación, no se eliminan**; se muestran con advertencia. Las decisiones de moderación quedan en bitácora.

*Pregunta 1 (oficial vs reportado), a implementar en el corte 3:* el precio oficial se muestra etiquetado como "publicado por el comercio"; si ≥ 2 reportes independientes recientes difieren en más de 5 %, se muestran ambos con advertencia.

## Consecuencias
- **Positivas:** explicable al consumidor y al comercio; probable con TDD; robusto con pocos datos.
- **Negativas:** umbrales sensibles (punto de sensibilidad); un ataque coordinado con muchas cuentas antiguas podría pasar → moderación y límites.
- **Verificación:** I-03 con conjunto etiquetado (M-03 ≥ 80 %, M-04 ≤ 5 %).
