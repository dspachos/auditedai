
## Technical information about the VP module

1. CORS configuration example in `services.yml`

```sh
parameters:
  ...
  cors.config:
   enabled: true
   allowedHeaders: ['x-csrf-token','authorization','content-type','accept','origin','x-requested-with', 'access-control-allow-origin','x-allowed-header','*']
   allowedMethods: ['*']
   allowedOrigins: ['http://localhost/','http://localhost:3000','http://localhost:3001','http://localhost:3002','*']
   exposedHeaders: ['false']
   maxAge: false
   supportsCredentials: false
```
