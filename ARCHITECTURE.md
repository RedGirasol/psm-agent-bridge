# Arquitectura del bridge multi-proveedor

## Visión general

Este paquete expone un contrato común para ejecutar agentes sin acoplar el consumo de la app a Claude ni a Codex.

La app usa una fachada (`Agent`) y un manager que resuelve el driver apropiado según la configuración.

## Diagrama de arquitectura

```mermaid
flowchart LR
    A[PSM / app] --> B[Facade Agent]
    B --> C[AgentManager]
    C --> D[driver: fake]
    C --> E[driver: claude]
    C --> F[driver: codex]

    E --> G[ClaudeAgentDriver]
    G --> H[Claude SDK / CLI]

    F --> I[CodexAgentDriver]
    I --> J[Codex SDK / CLI]

    subgraph Bridge Laravel
      B
      C
      D
      E
      F
      G
      I
    end

    subgraph Repos distintos
      H
      J
    end
```

## Flujo de uso

```mermaid
sequenceDiagram
    participant App as PSM App
    participant Facade as Agent Facade
    participant Manager as AgentManager
    participant Driver as Claude/Codex Driver
    participant SDK as Claude SDK / Codex SDK

    App->>Facade: Agent::run(request)
    Facade->>Manager: driver()
    Manager->>Driver: run(request)
    Driver->>SDK: query/exec
    SDK-->>Driver: result payload
    Driver-->>Manager: AgentResponse
    Manager-->>Facade: AgentResponse
    Facade-->>App: AgentResponse
```

## Diagrama de clases

```mermaid
classDiagram
    class AgentRequest {
        +string prompt
        +string|null systemPrompt
        +string|null cwd
        +list<string> addDirs
        +array<string, string> env
        +string|null model
        +int|null maxTurns
        +bool stream
    }

    class AgentResponse {
        +array payload
        +bool ok
        +string|null error
    }

    class AgentDriver {
        <<interface>>
        +run(request: AgentRequest) AgentResponse
    }

    class AgentManager {
        +getDefaultDriver() string
        +driver(name?: string) AgentDriver
    }

    class FakeAgentDriver {
        +run(request: AgentRequest) AgentResponse
    }

    class ClaudeAgentDriver {
        +run(request: AgentRequest) AgentResponse
    }

    class CodexAgentDriver {
        +run(request: AgentRequest) AgentResponse
    }

    class Agent {
        <<facade>>
        +run(request: AgentRequest) AgentResponse
    }

    AgentRequest --> AgentDriver
    AgentResponse --> AgentDriver
    AgentDriver <|.. FakeAgentDriver
    AgentDriver <|.. ClaudeAgentDriver
    AgentDriver <|.. CodexAgentDriver
    AgentManager --> AgentDriver
    Agent --> AgentManager
```

## Principio de diseño

La app no conoce el proveedor real. Solo conoce el contrato:

- `AgentRequest`
- `AgentResponse`
- `AgentDriver`

Cada driver adapta el contrato a un SDK concreto:

- `ClaudeAgentDriver` para Claude
- `CodexAgentDriver` para Codex
- `fake` para pruebas

Esto permite cambiar de proveedor sin tocar el dominio de la aplicación.
