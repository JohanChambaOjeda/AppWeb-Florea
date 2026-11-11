# Prueba de Florea con dos personas reales

Guion para probar la app con **tu amiga** (vendedora de poscosecha) y **tu
mamá** (productora), cada una en su propio celular, antes del piloto del
**10 de noviembre de 2026**.

Dura entre **45 minutos y una hora**. Necesitas a las dos al mismo tiempo en
la segunda parte, porque una tiene que publicar para que la otra reciba el
aviso.

---

## La única regla: no ayudar

Esto no es una demostración. Es una prueba de si la app se entiende sola,
porque el 10 de noviembre no vas a estar al lado de nadie.

**Si tú explicas, la prueba no sirve.** Lo que aprendes cuando ayudas es que
la app funciona _contigo al lado_, y eso ya lo sabes.

Cuesta callarse. Por eso, cuando una se trabe, sigue esta escalera y **anota
en qué escalón tuviste que intervenir**:

| Escalón | Qué haces                           | Qué significa                      |
| ------- | ----------------------------------- | ---------------------------------- |
| 0       | Nada. Cuentas hasta 30 en silencio. | Se desatascó sola                  |
| 1       | "¿Qué estás pensando?"              | Solo necesitaba pensar en voz alta |
| 2       | "¿Qué crees que hace ese botón?"    | La pantalla no es clara            |
| 3       | Le dices dónde tocar                | **Esto hay que arreglarlo**        |
| 4       | Lo haces tú                         | **Esto bloquea el piloto**         |

Cualquier cosa que llegue a 3 o 4 es trabajo pendiente, no un despiste de
ella. Si te sale la frase "es que es fácil, mira", respira y anota el 3.

Dos frases que sí puedes decir todo el tiempo:

- "No hay forma de equivocarse, la que está en prueba es la app."
- "Dime en voz alta lo que vas pensando."

---

## Antes del día

### En la app (esto es tu tarea, hazlo con calma el día anterior)

- [ ] La app está en producción y abre por **https://**
- [ ] La línea de cron está puesta y funcionando (sin eso **no llegan los
      avisos** y la segunda parte se cae)
- [ ] `php artisan storage:link` hecho, o las fotos saldrán roto
- [ ] Las llaves VAPID generadas **en el servidor**
- [ ] Hay variedades en el catálogo: Freedom, Explorer, Mondial, Pink Floyd,
      Vendela y las que de verdad se mueven en Tabacundo
- [ ] Tu usuario admin entra a `/admin`

### Las dos cuentas

Las dos se registran **solas** (eso es la tarea 1), pero hay un paso que
depende de ti: **solo las empresas verificadas pueden publicar u ofertar**.
En cuanto se registren, entras a `/admin/companies` y las verificas. Si no,
las dos se quedan mirando "cuenta en revisión" y la prueba termina ahí.

> Ten `/admin/companies` abierto en tu celular desde el principio. Vas a
> necesitarlo dos veces en los primeros diez minutos.

### Lo que tiene que haber de verdad

- [ ] **Rosas de verdad** donde esté tu mamá. La tarea de la foto es tomar una
      foto de flor del día; con una foto de la galería no pruebas nada.
- [ ] Las dos con datos móviles o wifi, y batería.
- [ ] Saber qué celular tiene cada una (ver el recuadro de abajo).

### Si alguna tiene iPhone

Cambia el guion de la tarea 2:

- En iPhone no sale el cartel de "Instalar". Hay que entrar **con Safari**,
  tocar el botón de compartir (el cuadrito con la flecha) y buscar **"Añadir
  a pantalla de inicio"**.
- Y lo importante: **en iPhone los avisos solo funcionan si la app está
  instalada así**. Si se queda en el navegador, no le va a llegar nada, y no
  es un fallo tuyo.
- Necesita iOS 16.4 o más nuevo. Compruébalo antes, no en el momento.

Anota cuál es cuál, porque los resultados no se comparan entre los dos:

|                   | Celular | Sistema | Navegador |
| ----------------- | ------- | ------- | --------- |
| Amiga (vendedora) |         |         |           |
| Mamá (productora) |         |         |           |

---

## Cómo medir el tiempo

Con el cronómetro del celular. Arranca cuando terminas de decir la frase y
para cuando ella ve el resultado en pantalla. Si se traba, **no pares el
cronómetro**: el tiempo con atasco es el dato interesante.

Apunta los minutos redondeados. Nadie necesita segundos.

---

## Parte 1 — Cada una por su lado (15 min)

Puedes hacer esta parte con una y luego con la otra, o en paralelo si tienes
quien te ayude a observar. **Que no se vean entre ellas**: si una mira cómo lo
hace la otra, la segunda ya no está probando nada.

**Lo que le dices, textual:**

1. "Entra a florea.lat y crea tu cuenta." _(No digas cómo. Solo dale la
   dirección, escrita, no dictada.)_
2. "Haz que la app quede en tu pantalla de inicio, como WhatsApp."
3. "Haz que te avisen al celular cuando pase algo en Florea."
4. (Solo a tu mamá) "Dile a la app qué variedades tienes tú."

| #   | Tarea                        | ¿Lo logró sola? | Tardó | ¿Dónde se trabó? |
| --- | ---------------------------- | --------------- | ----- | ---------------- |
| 1   | Crear la cuenta              |                 |       |                  |
| 2   | Instalar la app              |                 |       |                  |
| 3   | Activar los avisos           |                 |       |                  |
| 4   | Elegir sus variedades (mamá) |                 |       |                  |

Cosas que mirar sin decir nada en la tarea 1:

- ¿Escribe el celular con 09… o con +593? Las dos formas tienen que servir.
- ¿Lee lo de aceptar los términos o lo salta? _(No le digas nada. Solo anota.)_
- ¿Entiende qué es "poscosecha" y qué es "productor", o duda?

Y en la 3: cuando el navegador pregunte por los avisos, **no opines**. Si dice
"Bloquear", anótalo y sigue. Es un resultado, no un error: significa que el
momento en que lo pedimos está mal elegido.

> Apunta las variedades que eligió tu mamá. Las necesitas en la parte 2.

---

## Parte 2 — Las dos a la vez (20 min)

Aquí las dos tienen que estar disponibles al mismo tiempo. Tu amiga publica,
tu mamá recibe el aviso y oferta, tu amiga compara y acepta.

### Tarea 5 — La vendedora publica un "Busco"

**Lo que le dices:** "Te faltan tres variedades para cerrar un pedido. Pídelas
en la app: **tres variedades distintas y cada una de un largo diferente**."

Antes de decirlo, decide tú las tres y **que al menos una sea de las que eligió
tu mamá**, o no le va a llegar ningún aviso. Escríbelas en un papel y dáselo,
para que no tenga que inventarse nada:

```
Lo que te falta:

  3 HB   ______________  70 cm
  5 FB   ______________  60 cm
  2 QB   ______________  50 cm
```

| #   | Tarea                           | ¿Lo logró sola? | Tardó | ¿Dónde se trabó? |
| --- | ------------------------------- | --------------- | ----- | ---------------- |
| 5   | Publicar el "Busco" de 3 líneas |                 |       |                  |

Lo que hay que mirar:

- ¿Encuentra el botón de publicar, o lo busca por toda la pantalla?
- ¿Entiende "Busco flor" frente a "Tengo disponible"?
- ¿Encuentra cómo **añadir la segunda y la tercera línea**? Este es el punto
  donde más esperaría que se trabe.
- ¿Elige los largos sin problema? ¿Busca un largo que no está?
- ¿Entiende FB, HB y QB, o pregunta?
- ¿Se da cuenta de que el pedido dura 48 horas?

### Tarea 6 — A la productora le llega el aviso

**No le dices nada.** Deja el celular de tu mamá a la vista y espera.

El aviso puede tardar **hasta un par de minutos**: la cola se vacía cada
minuto. No es lentitud de la app, es cómo funciona el servidor compartido.

| #   | Tarea                                   | ¿Pasó? | Tardó | Notas |
| --- | --------------------------------------- | ------ | ----- | ----- |
| 6   | Le llegó el aviso al celular            |        |       |       |
| 6b  | Toca el aviso y abre el pedido correcto |        |       |       |

Si **no llega**, no rescates la prueba todavía: pídele que entre a la app y
mire si hay algo nuevo. Que lo encuentre sin el aviso también es un dato. Pero
anótalo como fallo grande: el aviso es la razón de ser de Florea.

### Tarea 7 — La productora oferta con foto

**Lo que le dices:** "Tú tienes esa flor. Ofrécela."

| #   | Tarea                     | ¿Lo logró sola? | Tardó | ¿Dónde se trabó? |
| --- | ------------------------- | --------------- | ----- | ---------------- |
| 7   | Ofertar con foto y precio |                 |       |                  |

Lo que hay que mirar:

- ¿Entiende que la foto es **obligatoria** y que es del día?
- ¿Se abre la cámara de un toque, o se pierde buscando en la galería?
- La foto que sale, ¿se ve bien? ¿Sale **de lado**? _(Si sale de lado, falta
  `exif` en el servidor.)_
- El precio: ¿escribe `0.45`, `0,45` o `45`? Las tres cosas pasan en la vida
  real. Anota exactamente lo que escribió.
- ¿Entiende que el precio es **por tallo**?
- Si tarda mucho subiendo la foto, anota cuánto. En el cultivo la señal es
  peor que en tu casa.

### Tarea 8 — La vendedora compara, acepta y escribe

**Lo que le dices:** "Mira lo que te ofrecieron y cierra con quien más te
convenga."

| #   | Tarea                              | ¿Lo logró sola? | Tardó | ¿Dónde se trabó? |
| --- | ---------------------------------- | --------------- | ----- | ---------------- |
| 8   | Comparar y aceptar                 |                 |       |                  |
| 8b  | Escribir por WhatsApp desde la app |                 |       |                  |

Lo que hay que mirar:

- ¿Mira la foto antes de aceptar? ¿La amplía?
- ¿Entiende el aviso de que al aceptar **se muestra su celular**? Esto importa:
  es la decisión de privacidad de toda la app.
- Después de aceptar, ¿encuentra el botón de WhatsApp?
- Cuando se abre WhatsApp, ¿el mensaje que viene escrito le sirve o lo borra?
  **Si lo borra y escribe otra cosa, copia lo que escribió**: eso es el
  mensaje que deberíamos poner nosotros.

---

## Parte 3 — Calificar (5 min)

El trato ya existe. Las dos pueden calificar desde la tarjeta verde del trato.

**Lo que les dices, a cada una:** "Di cómo te fue con ella."

| #   | Tarea                  | ¿Lo logró sola? | Tardó | ¿Dónde se trabó? |
| --- | ---------------------- | --------------- | ----- | ---------------- |
| 9   | Calificar (vendedora)  |                 |       |                  |
| 9b  | Calificar (productora) |                 |       |                  |

Lo que hay que mirar:

- ¿Encuentra dónde calificar, o lo busca?
- La pregunta "¿Cuánto se rechazó?" solo le sale a tu amiga, que es quien
  compra. ¿La entiende? ¿Le incomoda contestarla?
- ¿Le molesta calificar, o lo hace sin problema? Si pone cinco estrellas sin
  pensar, anótalo: las calificaciones de compromiso no sirven de nada.

> **Las estrellas no van a aparecer todavía.** Hacen falta 3 calificaciones
> para que una empresa deje de ser "Nuevo en Florea". Está hecho a propósito,
> para que una sola opinión no marque a nadie. Si alguna pregunta por qué no
> ve su nota, ya sabes.

---

## Preguntas del final

Hazlas **por separado**, cuando ya guardaron el celular. Delante de la otra
nadie dice lo que piensa de verdad.

### A las dos

1. **¿Qué fue lo más confuso?** _(Cállate después de preguntar. La respuesta
   buena llega a los diez segundos, no al primer "nada, todo bien".)_
2. **¿Qué le falta para que la usarías mañana?**
3. **La próxima vez que te falte flor / que te sobre flor, ¿la vas a abrir?**
   Y luego: **¿por qué?** El "por qué" vale más que el sí.
4. ¿Se lo recomendarías a alguien del cantón? ¿A quién?
5. ¿Hay algo que te dio desconfianza?

### Solo a tu amiga (la vendedora, que es quien paga)

6. Hoy, cuando te falta flor para cerrar un pedido, **¿qué haces?** ¿A cuántas
   personas llamas? ¿Cuánto tiempo te toma?
7. ¿Cuánto te cuesta **no** conseguirla? ¿Has perdido un pedido por eso?
8. **¿Cuánto pagarías al mes por esto?**

> La 8 es la pregunta que peor funciona de toda la lista, porque te va a
> querer dar gusto. Protégela así:
>
> - Pregúntala **al final**, nunca al principio.
> - Antes, pregunta la 6 y la 7. Si acaba de decirte que pierde media mañana
>   llamando, el número que diga después significa algo.
> - No digas tú una cifra primero. Si te la pide, devuélvela: "¿Qué te
>   parecería razonable a ti?"
> - Y después pregunta lo que de verdad importa: **"¿lo pagarías el mes que
>   no cerraste ningún trato por aquí?"** Ahí se ve si el precio aguanta.
>
> Trata su respuesta como un indicio, no como un precio. El precio lo dice
> quien paga de verdad, no quien dice que pagaría.

---

## Qué hago con lo anotado

Esa misma noche, antes de que se te olvide el tono con que dijeron las cosas:

1. Pasa los atascos de escalón **3 y 4** a una lista. Esos son el trabajo.
2. Ordénalos por **cuántas de las dos** se trabaron ahí. Si las dos se
   trabaron en lo mismo, eso va primero, sin discusión.
3. Las frases textuales, cópialas tal cual. "No sé si ya se mandó" dice más
   que "problema de confirmación".
4. Lo que te sorprendió, apúntalo aparte. Es lo más valioso y lo primero que
   se olvida.

### Cómo sé si salió bien

| Señal                                          | Qué significa                                       |
| ---------------------------------------------- | --------------------------------------------------- |
| Las dos llegaron al final con ceros y unos     | Listo para el 10                                    |
| Algún escalón 3 en publicar u ofertar          | Se arregla y se vuelve a probar **esa pantalla**    |
| **Un escalón 4 en cualquier parte**            | No se abre el piloto hasta arreglarlo               |
| **El aviso no llegó**                          | Para todo y revisa el cron. Sin aviso no hay Florea |
| Publicar pasó de 6 o 7 minutos                 | Demasiado. Hay que quitar pasos                     |
| Ofertar con foto pasó de 5 minutos             | Mirar la cámara y la subida con señal mala          |
| Dijo "sí lo usaría" pero no supo decir por qué | Trátalo como un no                                  |

Lo que **no** es un resultado válido: "le gustó". Tu mamá te va a decir que le
gustó. Mira los números y los atascos, no las caras.

---

## Después

Vuelve a `/admin/metrics` y comprueba que la prueba se ve ahí: un pedido, una
oferta, un trato, y el tiempo hasta la primera oferta. Si la prueba **no**
aparece en las métricas, las métricas están mal y el 10 de noviembre vas a
estar a ciegas. Es la única forma de comprobarlas con datos de verdad antes
del piloto.

### Haz la prueba antes del lunes 9

No por costumbre, por los números: las métricas van **por semana**, de lunes a
domingo. Si pruebas el jueves 5, los datos de la prueba caen en la semana del
2 al 8 y **la semana del piloto empieza limpia**. Si pruebas el martes 10,
este pedido de mentira se te queda dentro de la primera semana del piloto,
subiendo el porcentaje de "contestados en menos de 4 horas" con una oferta que
hizo tu mamá porque tú le pediste que la hiciera.

Y eso no se puede deshacer: **el panel no tiene botón de borrar
publicaciones ni tratos**, a propósito, para que nadie haga desaparecer un
trato incómodo. Si acabas teniendo que limpiarlo, es a mano por SQL, y es
mucho más fácil elegir bien la fecha.

Lo que sí conviene dejar como está: **las dos cuentas verificadas**. Son
clientas reales del piloto, no datos de prueba.
