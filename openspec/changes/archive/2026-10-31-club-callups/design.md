## Decisiones

- **Tokens con el club adelante** (`<club>.<aleatorio>`): cada club tiene su base, así que la API ubica el club sin login. El aleatorio tiene 40 caracteres.
- **Solo habilitados:** la lista rechaza jugadores que no están en el plantel o no están habilitados (TIZ-32) y dice qué les falta.
- **La lista se reemplaza entera;** los que siguen conservan su respuesta y su link.
- **Los textos los arma la API** (`CallupMessages`) para que WhatsApp y el mail digan lo mismo. Los links apuntan al front con `FRONTEND_URL`.
- **WhatsApp sin la API de Meta:** links `wa.me` al teléfono del contacto principal con el mensaje armado. No cuesta nada ni pide aprobación.
- **Datos mínimos en lo público:** nombre e inicial del apellido y número. Nunca DNI, contactos ni documentos.
- **Republicar** mantiene el mismo link público y limpia `updated_after_publish`.
