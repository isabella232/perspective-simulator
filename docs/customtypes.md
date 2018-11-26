# Custom Type Actions

## Custom Data Type

**Usage for:**

```bash
perspective <action> customdatatype <arguments>
```

### Add

Adds a new Custom Data Type to the project

```bash
perspective [-p] add customdatatype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

#### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

### Delete

Deletes a Custom Data Type from the project

```bash
perspective [-p] delete customdatatype <customTypeCode>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Rename

Renames a Custom Data Type in the project

```bash
perspective [-p] rename customdatatype <oldCustomTypeCode> <newCustomTypeCode>
```

#### Required arguments:

| Argument          |                                       |
| ----------------- | ------------------------------------- |
| oldCustomTypeCode | The current code for the custom type. |
| newCustomTypeCode | The new code for the custom type.     |

### Move

Moves a Custom Data Type in the project

```bash
perspective [-p] move customdatatype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

## Custom Page Type

**Usage for:**

```bash
perspective <action> custompagetype <arguments>
```

### Add

Adds a new Custom Page Type to the project

```bash
perspective [-p] add custompagetype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

#### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

### Delete

Deletes a Custom Page Type from the project

```bash
perspective [-p] delete custompagetype <customTypeCode>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Rename

Renames a Custom Page Type in the project

```bash
perspective [-p] rename custompagetype <oldCustomTypeCode> <newCustomTypeCode>
```

#### Required arguments:

| Argument          |                                       |
| ----------------- | ------------------------------------- |
| oldCustomTypeCode | The current code for the custom type. |
| newCustomTypeCode | The new code for the custom type.     |

### Move

Moves a Custom Page Type in the project

```bash
perspective [-p] move custompagetype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

