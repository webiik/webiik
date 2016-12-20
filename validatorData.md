# ValidatorData
ValidatorData is part of [Webiik](readme.md). It is used in common with [Validator](validator.md).  

## Description of provided methods

- `filter(string $name, array $params):ValidatorData`
Adds filter. Returns ValidatorData object. If 'msg' param exists, Validator uses it like message.

- `getFilters:array`
Returns added filters.

- `getData:mixed`
Returns data.

- `isRequired:bool`
Returns true if required, otherwise false.