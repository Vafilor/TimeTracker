# Design

Notes on various design features of the code.

## API Errors

The API can return multiple errors for a request. This is to allow the client to fix many issues in one go
instead of doing it one by one per request.

The structure is as follows
```json
{
  "status": 400, // the http status code, for convenience
  "type": "invalid_action", // a generic reason for the error
  "title": "Invalid action", // a generic reason, in human friendly format
  "errors": [
    {
      "code": "code_running_timer", // the error code, unique so it is easy to identify in the client
      "message": "You have a running timer" // human friendly error message
      "resource": "35082d3b-4688-4532-a210-eaad7bf2396c", // extra data field, specific to the endpoint.
    }
  ]
```

In the above example, `resource` is a custom data field, in this particular request, it is used to indicate
the currently running timer in case the client wants to link it to the user, or some other feature.
