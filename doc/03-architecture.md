# Architecture

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Retype Planning](04-retype-planning.md)

The architecture follows the `php-rename` model while keeping type-specific semantics explicit.

## Domain

`Domain/Retype` contains the core retype model:

- `MethodParameterTypeChangeRequest`: describes a method parameter type-change intent.
- `FunctionParameterTypeChangeRequest`: describes a function parameter type-change intent.
- `MethodReturnTypeChangeRequest`: describes a method return type-change intent.
- `FunctionReturnTypeChangeRequest`: describes a function return type-change intent.
- `RetypePlan`: contains planned operations and diagnostics.
- `RetypeOperation`: targets one AST node in one virtual file.
- `RetypeResult`: contains the result of applying a plan.
- `RetypeStepContext`: carries the member graph build used by one orchestrated step.
- `RetypeStepResult`: contains the next context, applied plan, diagnostics, touched files, and graph refresh state for one step.
- `RetypeDiagnostic`: reports planning or application information.
- `RetypeDiagnosticSeverity`: identifies informational, warning, and error diagnostics.
- `RetypeTargetKind`: identifies the kind of target being retyped.
- `RetypeOperationRole`: identifies why a node is part of the plan.
- `RetypeInputValidator`: validates public inputs before planning.

Domain objects should stay independent from orchestration logic.

## Application

`Application/PhpRetype` is the public facade.

It exposes:

- `fromDirectory()`;
- `fromBuild()`;
- `planMethodParameterTypeChange()`;
- `changeMethodParameterType()`;
- `planFunctionParameterTypeChange()`;
- `changeFunctionParameterType()`;
- `planMethodReturnTypeChange()`;
- `changeMethodReturnType()`;
- `planFunctionReturnTypeChange()`;
- `changeFunctionReturnType()`;
- `executeStep()`;
- `executeStepMethodParameterTypeChange()`;
- `executeStepFunctionParameterTypeChange()`;
- `executeStepMethodReturnTypeChange()`;
- `executeStepFunctionReturnTypeChange()`.

`Application/Contract` contains the service contracts used by the facade:

- `MethodParameterTypeChangePlannerInterface`;
- `FunctionParameterTypeChangePlannerInterface`;
- `MethodReturnTypeChangePlannerInterface`;
- `FunctionReturnTypeChangePlannerInterface`;
- `RetypePlanApplierInterface`.

## Infrastructure

`Infrastructure/MemberGraph` translates semantic graph facts into retype operations.

`Infrastructure/PhpParser` applies retype operations to PHPParser AST nodes stored in virtual files.

Infrastructure code can depend on external packages. Domain objects should remain simple and explicit.

## Retype Application

`AstRetypePlanApplier` orchestrates specialized appliers:

- it does not apply plans that contain error diagnostics;
- node appliers mutate matched AST nodes;
- metadata appliers mutate supported metadata attached to a successfully mutated node;
- touched virtual files are marked updated through `VirtualPhpSourceFile::update()`.

Current applier contracts:

- `RetypeNodeApplierInterface`;
- `RetypeMetadataApplierInterface`.

Current implementations:

- `ParameterTypeNodeApplier`;
- `MethodReturnTypeNodeApplier`;
- `FunctionReturnTypeNodeApplier`;
- `ParameterDocblockTypeApplier`;
- `ReturnDocblockTypeApplier`.

## Step Execution

`Application/RetypeStepExecutor` is the transaction-neutral execution boundary used by higher-level orchestrators.

It performs one step:

- aggregate planning diagnostics;
- collect touched virtual files from plan operations;
- skip application when the plan contains errors;
- apply the plan to the current context build;
- aggregate application diagnostics;
- rebuild the current member graph from mutated virtual files when operations were applied.

This rebuild is intentionally stricter than the rename overlay model. A type replacement can change source-node relationships, PHPDoc facts, and future planning outcomes. Until `member-graph` exposes a dedicated type-mutation projection API, rebuilding from the changed AST is the reliable integration contract.

## Design Rule

Do not add a broad refactoring abstraction before the method parameter type-change path is proven.

The package should grow from concrete safe type-change workflows, then generalize only when duplication becomes real.

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Retype Planning](04-retype-planning.md)
