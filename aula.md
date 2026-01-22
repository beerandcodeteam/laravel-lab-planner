# Aula: Construindo a Camada de Dados do Planner (POC)

Nesta aula, vamos construir a base sólida do nosso aplicativo "Planner POC". Focaremos na modelagem do banco de dados, criação das tabelas via Migrations e na estruturação dos Models e Enums no Laravel.

## 1. Visão Geral e Modelagem

O sistema gira em torno de **Metas (Goals)** e **Tarefas (Tasks)**.
*   Um **Usuário** define **Metas**.
*   Cada **Meta** possui um status (Situação), um diagnóstico e perguntas de reflexão.
*   Para atingir uma Meta, o usuário cria **Tarefas**.
*   Cada **Tarefa** tem um tipo (Ex: Hábito ou Única) e um passo/estágio (Ex: A Fazer, Feito).

## 2. Migrations: Criando as Tabelas

Vamos criar as tabelas seguindo a ordem de dependência (primeiro as tabelas independentes, depois as que possuem chaves estrangeiras).

### 2.1. Tabelas de Domínio (Lookups)
Estas tabelas armazenam as opções disponíveis para classificarmos nossas metas e tarefas.

**Tabela: `task_types`** (Tipos de Tarefa)
```php
Schema::create('task_types', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Ex: Single, Habit
    $table->string('icon')->nullable();
    $table->string('color')->nullable();
    $table->timestamps();
});
```

**Tabela: `task_steps`** (Etapas da Tarefa - Kanban)
```php
Schema::create('task_steps', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Ex: To-Do, Doing, Done
    $table->string('icon')->nullable();
    $table->string('color')->nullable();
    $table->timestamps();
});
```

**Tabela: `goal_situations`** (Situação da Meta)
```php
Schema::create('goal_situations', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Ex: In Progress, Achieved
    $table->string('icon')->nullable();
    $table->string('color')->nullable();
    $table->timestamps();
});
```

### 2.2. Tabela Principal: Goals
A tabela de metas conecta o usuário à situação atual do objetivo.

**Tabela: `goals`**
```php
Schema::create('goals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('goal_situation_id')->constrained(); // FK para goal_situations
    $table->foreignId('user_id')->constrained('users');    // FK para users
    $table->string('name');
    $table->date('deadline');
    $table->text('description');
    $table->text('self_situation'); // Campo livre para auto-avaliação
    $table->timestamps();
});
```

### 2.3. Tabelas Satélites de Goals
Tabelas que adicionam detalhes específicos a uma meta.

**Tabela: `goal_questions`** (1 Meta -> N Perguntas)
```php
Schema::create('goal_questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('goal_id')->constrained();
    $table->text('question');
    $table->text('answer')->nullable();
    $table->integer('order');
    $table->timestamps();
});
```

**Tabela: `goal_diagnoses`** (1 Meta -> 1 Diagnóstico)
```php
Schema::create('goal_diagnoses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('goal_id')->constrained();
    $table->text('description');
    $table->timestamps();
});
```

### 2.4. Tabela Operacional: Tasks
As tarefas conectam-se à meta e aos seus classificadores (tipo e passo).

**Tabela: `tasks`**
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('goal_id')->constrained();
    $table->foreignId('task_type_id')->constrained();
    $table->foreignId('task_step_id')->constrained();
    $table->string('title');
    $table->integer('week_prevision')->nullable();
    $table->integer('order');
    $table->dateTime('scheduled_date')->nullable();
    $table->dateTime('completed_at')->nullable();
    $table->timestamps();
});
```

---

## 3. Enums e Models

Agora vamos representar essas estruturas no código PHP.

### 3.1. Enums
Usamos Enums para ter constantes fortes no código, úteis para comparações e lógica de negócios.

**`App\Enums\GoalSituationEnum`** (String Backed)
```php
enum GoalSituationEnum: string
{
    case InProgress = 'in-progress';
    case Achieved = 'achieved';
    case Abandoned = 'abandoned';

    public function label(): string { /* ... */ }
}
```
*Também temos `TaskTypeEnum` e `TaskStepEnum` seguindo o mesmo padrão.*

### 3.2. Models e Relacionamentos

Aqui definimos como os objetos interagem.

**Model: `Goal`** (`app/Models/Goal.php`)
```php
class Goal extends Model
{
    protected $fillable = [
        'goal_situation_id', 'user_id', 'name', 'deadline', 'description', 'self_situation'
    ];

    protected function casts(): array
    {
        return [
            // Casting do ID para Enum (Requer cuidado na implementação)
            'goal_situation_id' => GoalSituationEnum::class,
            'deadline' => 'date',
        ];
    }

    // Relacionamentos
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function situation(): BelongsTo { return $this->belongsTo(GoalSituation::class, 'goal_situation_id'); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function questions(): HasMany { return $this->hasMany(GoalQuestion::class); }
    public function diagnosis(): HasOne { return $this->hasOne(GoalDiagnosis::class); }
}
```

**Model: `Task`** (`app/Models/Task.php`)
```php
class Task extends Model
{
    protected $fillable = [
        'goal_id', 'task_type_id', 'task_step_id', 'title', 'week_prevision', 
        'order', 'scheduled_date', 'completed_at'
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relacionamentos
    public function goal(): BelongsTo { return $this->belongsTo(Goal::class); }
    public function type(): BelongsTo { return $this->belongsTo(TaskType::class, 'task_type_id'); }
    public function step(): BelongsTo { return $this->belongsTo(TaskStep::class, 'task_step_id'); }
}
```

**Demais Models:**
*   `GoalQuestion`, `GoalDiagnosis`, `GoalSituation`, `TaskType`, `TaskStep` são models mais simples, focados em configurar os relacionamentos inversos (`belongsTo` ou `hasMany`).

---

## 4. Autenticação com Livewire Volt e Forms

Nesta seção, vamos implementar o fluxo de autenticação utilizando **Livewire Forms** para encapsular a lógica de validação e autenticação, mantendo nossos componentes **Volt** limpos.

### 4.1. Login com Rate Limiting

A segurança é primordial. Utilizamos o `RateLimiter` do Laravel para prevenir ataques de força bruta. O padrão aqui é separar a lógica do formulário da lógica de apresentação.

**Form Object: `app/Livewire/Forms/LoginForm.php`**

Esta classe herda de `Livewire\Form` e contém as propriedades e métodos para processar o login.

```php
class LoginForm extends Form
{
    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate()
    {
        $this->validate();

        // 1. Verifica se o usuário não excedeu o limite de tentativas
        $this->ensureIsNotRateLimited();

        // 2. Tenta autenticar usando o Auth::attempt padrão do Laravel
        if (! \Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            // Se falhar, incrementa o contador de tentativas
            \RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        // 3. Limpa o limitador se o login for bem-sucedido
        \RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! \RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        
        // Lança exceção com tempo restante se excedeu o limite
        $seconds = \RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
             'form.email' => trans('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    // Gera uma chave única baseada no email e IP
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
```

**Componente Volt: `resources/views/pages/auth/login.blade.php`**

O componente visual utiliza a sintaxe funcional do Volt.

```php
use App\Livewire\Forms\LoginForm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public LoginForm $form;

    public function login()
    {
        // Chama a lógica de autenticação encapsulada no Form Object
        $this->form->authenticate();

        session()->regenerate();

        return $this->redirect(
            session('url.intended', route('home')), 
            navigate: true
        );
    }
};
```

Na view Blade, fazemos o bind direto com o objeto form: `wire:model="form.email"`.

### 4.2. Fluxo de Cadastro (Registration)

Para o cadastro, seguimos o mesmo padrão, utilizando um Form Object para validar e criar o usuário.

**Form Object: `app/Livewire/Forms/RegisterForm.php`**

```php
class RegisterForm extends Form
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Regras de validação centralizadas
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function register() 
    {
        $this->validate();

        // Cria o usuário
        $user = User::create($this->all());

        // Faz o login automático após o cadastro
        auth()->login($user);
    }
}
```

**Componente Volt: `resources/views/pages/auth/register.blade.php`**

O componente apenas invoca o método do formulário:

```php
public function register(): void
{
    $this->form->register();

    session()->regenerate();

    $this->redirect(route('home'), navigate: true);
}
```

Isso demonstra o poder do Livewire em separar a lógica de negócios (Forms) da camada de apresentação (Componentes/Views), mantendo o código organizado, reutilizável e fácil de testar.