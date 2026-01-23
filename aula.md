# Roteiro de Aula: Modelagem de Banco de Dados com Laravel

## Parte 1: Entendendo o Domínio do Projeto

### 1.1 Visão Geral do Sistema

Este é um sistema de **Planejamento de Metas** (Planner) que possui as seguintes entidades principais:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           DOMÍNIO DO SISTEMA                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   User (Usuário)                                                        │
│     └── Goals (Metas)                                                   │
│           ├── GoalQuestions (Perguntas da Meta)                         │
│           ├── Diagnoses (Diagnósticos)                                  │
│           │     └── DiagnosisItems (Itens do Diagnóstico)               │
│           └── Tasks (Tarefas)                                           │
│                                                                         │
│   Tabelas de Suporte (Lookup Tables):                                   │
│     • GoalSituation (Status da Meta)                                    │
│     • DiagnosisStatus (Status do Diagnóstico)                           │
│     • DiagnosisPillar (Pilares do Diagnóstico)                          │
│     • DiagnosisItemType (Tipos de Item)                                 │
│     • TaskType (Tipos de Tarefa)                                        │
│     • TaskStep (Etapas da Tarefa - Kanban)                              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Diagrama de Entidade-Relacionamento (ER)

```
┌──────────────┐       ┌──────────────────┐
│    users     │       │  goal_situations │
├──────────────┤       ├──────────────────┤
│ id           │       │ id               │
│ name         │       │ name             │
│ email        │       │ icon             │
│ password     │       │ color            │
└──────┬───────┘       └────────┬─────────┘
       │                        │
       │ 1:N                    │ 1:N
       ▼                        ▼
┌──────────────────────────────────────────┐
│                  goals                    │
├──────────────────────────────────────────┤
│ id                                        │
│ user_id (FK)                              │
│ goal_situation_id (FK)                    │
│ name                                      │
│ deadline                                  │
│ description                               │
└───────┬────────────────┬─────────────────┘
        │                │
        │ 1:N            │ 1:N
        ▼                ▼
┌───────────────┐  ┌─────────────────┐
│goal_questions │  │   diagnoses     │◄──────┐
├───────────────┤  ├─────────────────┤       │
│ id            │  │ id              │       │
│ goal_id (FK)  │  │ goal_id (FK)    │       │
│ question      │  │ diagnosis_      │       │
│ answer        │  │   status_id(FK) │       │
│ order         │  │ description     │       │
└───────────────┘  └────────┬────────┘       │
                            │                │
                            │ 1:N            │
                            ▼                │
                   ┌─────────────────────┐   │
                   │  diagnosis_items    │   │
                   ├─────────────────────┤   │
                   │ id                  │   │
                   │ diagnosis_id (FK)   │   │
                   │ diagnosis_item_     │   │
                   │   type_id (FK)      │   │
                   │ diagnosis_pillar_   │   │   ┌───────────────────┐
                   │   id (FK)           │◄──┼───│diagnosis_statuses │
                   │ description         │   │   ├───────────────────┤
                   │ agent_selected_at   │   │   │ id                │
                   │ user_selected_at    │   │   │ name              │
                   └─────────────────────┘   │   └───────────────────┘
                            ▲                │
        ┌───────────────────┼────────────────┘
        │                   │
┌───────┴───────────┐  ┌────┴──────────────┐
│diagnosis_item_    │  │diagnosis_pillars  │
│       types       │  ├───────────────────┤
├───────────────────┤  │ id                │
│ id                │  │ name              │
│ name              │  └───────────────────┘
└───────────────────┘

        ┌─────────────────────────────────────────────┐
        │                   goals                      │
        └─────────────────────┬───────────────────────┘
                              │
                              │ 1:N
                              ▼
┌─────────────┐  ┌─────────────┐  ┌────────────────────────┐
│ task_types  │  │ task_steps  │  │         tasks          │
├─────────────┤  ├─────────────┤  ├────────────────────────┤
│ id          │  │ id          │  │ id                     │
│ name        │  │ name        │  │ goal_id (FK)           │
│ icon        │  │ icon        │  │ task_type_id (FK)      │
│ color       │  │ color       │  │ task_step_id (FK)      │
└──────┬──────┘  └──────┬──────┘  │ title                  │
       │                │         │ week_prevision         │
       │ 1:N            │ 1:N     │ order                  │
       └────────────────┴────────►│ scheduled_date         │
                                  │ completed_at           │
                                  └────────────────────────┘
```

---

## Parte 2: Criando as Migrations

### 2.1 Conceitos Importantes

**O que são Migrations?**
- São como um "controle de versão" para o banco de dados
- Permitem criar, modificar e excluir tabelas de forma programática
- Facilitam o trabalho em equipe (todos têm a mesma estrutura)

**Comando para criar migrations:**

```bash
vendor/bin/sail artisan make:migration create_nome_da_tabela_table
```

### 2.2 Ordem de Criação das Migrations

A ordem é **fundamental** por causa das Foreign Keys. Tabelas referenciadas devem existir antes.

```
1. Tabelas de Suporte (sem dependências):
   ├── task_types
   ├── task_steps
   ├── goal_situations
   ├── diagnosis_statuses
   ├── diagnosis_item_types
   └── diagnosis_pillars

2. Tabelas Principais (com dependências):
   ├── goals (depende de: users, goal_situations)
   ├── goal_questions (depende de: goals)
   ├── diagnoses (depende de: goals, diagnosis_statuses)
   ├── diagnosis_items (depende de: diagnoses, diagnosis_item_types, diagnosis_pillars)
   └── tasks (depende de: goals, task_types, task_steps)
```

### 2.3 Migrations - Tabelas de Suporte

#### 2.3.1 Task Types (Tipos de Tarefa)

```php
<?php

use App\Models\TaskType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // Seed inicial - dados padrão do sistema
        TaskType::insert([
            ['name' => 'single'],
            ['name' => 'habit'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_types');
    }
};
```

**Pontos de atenção:**
- `$table->id()` cria um campo `BIGINT UNSIGNED AUTO_INCREMENT`
- `$table->timestamps()` cria `created_at` e `updated_at`
- `->nullable()` permite valores nulos
- O `insert()` no `up()` é útil para dados que são parte do sistema

---

#### 2.3.2 Task Steps (Etapas - Kanban)

```php
<?php

use App\Models\TaskStep;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        TaskStep::insert([
            ['name' => 'Backlog'],
            ['name' => 'To-Do'],
            ['name' => 'Doing'],
            ['name' => 'Done'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_steps');
    }
};
```

---

#### 2.3.3 Goal Situations (Status da Meta)

```php
<?php

use App\Models\GoalSituation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_situations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        GoalSituation::insert([
            ['name' => 'In Progress'],
            ['name' => 'Achieved'],
            ['name' => 'Abandoned'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_situations');
    }
};
```

---

#### 2.3.4 Diagnosis Statuses (Status do Diagnóstico)

```php
<?php

use App\Models\DiagnosisStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DiagnosisStatus::insert([
            ['name' => 'creating'],
            ['name' => 'in-progress'],
            ['name' => 'completed'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_statuses');
    }
};
```

---

#### 2.3.5 Diagnosis Item Types (Tipos de Item do Diagnóstico)

```php
<?php

use App\Models\DiagnosisItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DiagnosisItemType::insert([
            ['name' => 'Domino bem'],
            ['name' => 'Preciso melhorar'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_item_types');
    }
};
```

---

#### 2.3.6 Diagnosis Pillars (Pilares do Diagnóstico)

```php
<?php

use App\Models\DiagnosisPillar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_pillars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DiagnosisPillar::insert([
            ['name' => 'Técnico'],
            ['name' => 'Estratégico'],
            ['name' => 'Comportamental'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_pillars');
    }
};
```

---

### 2.4 Migrations - Tabelas Principais

#### 2.4.1 Goals (Metas)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('goal_situation_id')->constrained();
            $table->string('name');
            $table->date('deadline');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
```

**Pontos de atenção:**
- `foreignId()` cria um campo `BIGINT UNSIGNED`
- `->constrained()` adiciona a Foreign Key automaticamente
- Por convenção, `foreignId('user_id')->constrained()` busca a tabela `users`
- `->constrained('users')` especifica explicitamente a tabela

---

#### 2.4.2 Goal Questions (Perguntas da Meta)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->integer('order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_questions');
    }
};
```

**Pontos de atenção:**
- `text()` para campos de texto longo
- `integer('order')` para ordenação das perguntas

---

#### 2.4.3 Diagnoses (Diagnósticos)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained();
            $table->foreignId('diagnosis_status_id')->nullable()->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
```

**Pontos de atenção:**
- `->default(1)` define valor padrão (status "creating")
- FK nullable com default permite criação incremental

---

#### 2.4.4 Diagnosis Items (Itens do Diagnóstico)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained();
            $table->foreignId('diagnosis_item_type_id')->constrained();
            $table->foreignId('diagnosis_pillar_id')->constrained();
            $table->text('description');
            $table->dateTime('agent_selected_at')->nullable();
            $table->dateTime('user_selected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_items');
    }
};
```

**Pontos de atenção:**
- Múltiplas FKs para tabelas de lookup
- Campos de datetime para rastrear seleções

---

#### 2.4.5 Tasks (Tarefas)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
```

---

### 2.5 Executando as Migrations

```bash
# Executar todas as migrations
vendor/bin/sail artisan migrate

# Verificar status das migrations
vendor/bin/sail artisan migrate:status

# Rollback da última migration
vendor/bin/sail artisan migrate:rollback

# Resetar tudo e rodar novamente
vendor/bin/sail artisan migrate:fresh
```

---

## Parte 3: Criando os Models

### 3.1 Conceitos Importantes

**O que são Models?**
- Representam as tabelas do banco como classes PHP
- Permitem interagir com os dados usando orientação a objetos
- Definem relacionamentos entre as entidades

**Comando para criar models:**

```bash
vendor/bin/sail artisan make:model NomeDoModel
```

### 3.2 Models - Tabelas de Suporte

#### 3.2.1 TaskType

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskType extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'icon',
        'color',
    ];

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

**Pontos de atenção:**
- `$fillable` define quais campos podem ser preenchidos em massa
- `hasMany()` define relacionamento 1:N (um tipo tem muitas tarefas)
- PHPDoc com tipos genéricos ajuda IDEs e análise estática

---

#### 3.2.2 TaskStep

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStep extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'icon',
        'color',
    ];

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

---

#### 3.2.3 GoalSituation

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalSituation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'icon',
        'color',
    ];

    /**
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
```

---

#### 3.2.4 DiagnosisStatus

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisStatus extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<Diagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
```

---

#### 3.2.5 DiagnosisItemType

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisItemType extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<DiagnosisItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DiagnosisItem::class);
    }
}
```

---

#### 3.2.6 DiagnosisPillar

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisPillar extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<DiagnosisItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DiagnosisItem::class);
    }
}
```

---

### 3.3 Models - Tabelas Principais

#### 3.3.1 User (já existe, adicionamos o relacionamento)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }
}
```

**Pontos de atenção:**
- User estende `Authenticatable` (não `Model`)
- `$hidden` esconde campos em serialização JSON
- `casts()` define conversões automáticas de tipos

---

#### 3.3.2 Goal

```php
<?php

namespace App\Models;

use App\Enums\GoalSituationEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'goal_situation_id',
        'user_id',
        'name',
        'deadline',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'goal_situation_id' => GoalSituationEnum::class,
            'deadline' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<GoalSituation, $this>
     */
    public function situation(): BelongsTo
    {
        return $this->belongsTo(GoalSituation::class, 'goal_situation_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<GoalQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(GoalQuestion::class);
    }

    /**
     * @return HasMany<Diagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
```

**Pontos de atenção:**
- `belongsTo()` define o lado "filho" do relacionamento
- Segundo parâmetro em `belongsTo()` quando o nome da FK não segue convenção
- Cast para Enum converte automaticamente o ID para o Enum

---

#### 3.3.3 GoalQuestion

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalQuestion extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'goal_id',
        'question',
        'answer',
        'order',
    ];

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
```

---

#### 3.3.4 Diagnosis

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnosis extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'diagnosis_status_id',
        'goal_id',
        'description',
    ];

    /**
     * @return BelongsTo<DiagnosisStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DiagnosisStatus::class, 'diagnosis_status_id');
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<DiagnosisItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DiagnosisItem::class);
    }
}
```

---

#### 3.3.5 DiagnosisItem

```php
<?php

namespace App\Models;

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosisItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'diagnosis_id',
        'diagnosis_item_type_id',
        'diagnosis_pillar_id',
        'description',
        'agent_selected_at',
        'user_selected_at',
    ];

    protected function casts(): array
    {
        return [
            'diagnosis_item_type_id' => DiagnosisItemTypeEnum::class,
            'diagnosis_pillar_id' => DiagnosisPillarEnum::class,
            'agent_selected_at' => 'datetime',
            'user_selected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * @return BelongsTo<DiagnosisItemType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DiagnosisItemType::class, 'diagnosis_item_type_id');
    }

    /**
     * @return BelongsTo<DiagnosisPillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(DiagnosisPillar::class, 'diagnosis_pillar_id');
    }
}
```

---

#### 3.3.6 Task

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'goal_id',
        'task_type_id',
        'task_step_id',
        'title',
        'week_prevision',
        'order',
        'scheduled_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return BelongsTo<TaskType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    /**
     * @return BelongsTo<TaskStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(TaskStep::class, 'task_step_id');
    }
}
```

---

## Parte 4: Resumo dos Relacionamentos

### 4.1 Tabela de Relacionamentos

| Model | Relacionamento | Model Relacionado | Tipo |
|-------|----------------|-------------------|------|
| User | goals | Goal | hasMany |
| Goal | user | User | belongsTo |
| Goal | situation | GoalSituation | belongsTo |
| Goal | tasks | Task | hasMany |
| Goal | questions | GoalQuestion | hasMany |
| Goal | diagnoses | Diagnosis | hasMany |
| GoalSituation | goals | Goal | hasMany |
| GoalQuestion | goal | Goal | belongsTo |
| Diagnosis | status | DiagnosisStatus | belongsTo |
| Diagnosis | goal | Goal | belongsTo |
| Diagnosis | items | DiagnosisItem | hasMany |
| DiagnosisStatus | diagnoses | Diagnosis | hasMany |
| DiagnosisItem | diagnosis | Diagnosis | belongsTo |
| DiagnosisItem | type | DiagnosisItemType | belongsTo |
| DiagnosisItem | pillar | DiagnosisPillar | belongsTo |
| DiagnosisItemType | items | DiagnosisItem | hasMany |
| DiagnosisPillar | items | DiagnosisItem | hasMany |
| Task | goal | Goal | belongsTo |
| Task | type | TaskType | belongsTo |
| Task | step | TaskStep | belongsTo |
| TaskType | tasks | Task | hasMany |
| TaskStep | tasks | Task | hasMany |

### 4.2 Exemplos de Uso dos Relacionamentos

```php
// Buscar todas as metas de um usuário
$user = User::find(1);
$goals = $user->goals;

// Buscar as tarefas de uma meta com eager loading
$goal = Goal::with(['tasks', 'questions', 'diagnoses'])->find(1);

// Buscar diagnóstico com todos os relacionamentos
$diagnosis = Diagnosis::with([
    'status',
    'goal',
    'items.type',
    'items.pillar'
])->find(1);

// Criar uma tarefa vinculada a uma meta
$goal->tasks()->create([
    'task_type_id' => 1,
    'task_step_id' => 1,
    'title' => 'Minha primeira tarefa',
    'order' => 1,
]);

// Buscar tarefas por step (Kanban)
$doingTasks = Task::whereHas('step', function ($query) {
    $query->where('name', 'Doing');
})->get();
```

---

---

## Parte 5: Design System - Componentes Blade

### 5.1 Visão Geral

O projeto possui um **Design System** construído com **componentes Blade** (não Livewire). Isso significa que são componentes de apresentação pura, sem estado no servidor.

**Importante:** Estes componentes usam **Alpine.js** para interatividade no cliente (modais, dropdowns, toggles), mas **não são componentes Livewire**.

```
resources/views/components/
├── button.blade.php          # Botões
├── card.blade.php            # Cards com slots
│   ├── header.blade.php
│   ├── body.blade.php
│   └── footer.blade.php
├── modal.blade.php           # Modais
├── section.blade.php         # Seções de página
│   ├── header.blade.php
│   └── content.blade.php
├── alert.blade.php           # Alertas/Notificações
│   ├── title.blade.php
│   └── description.blade.php
├── table.blade.php           # Tabelas
│   ├── head.blade.php
│   ├── body.blade.php
│   ├── row.blade.php
│   ├── header.blade.php
│   └── cell.blade.php
├── form/                     # Componentes de formulário
│   ├── input.blade.php
│   ├── select.blade.php
│   ├── textarea.blade.php
│   ├── checkbox.blade.php
│   ├── label.blade.php
│   ├── hint.blade.php
│   └── error.blade.php
├── sidebar/                  # Navegação lateral
│   ├── index.blade.php
│   ├── menu-item.blade.php
│   ├── menu-group.blade.php
│   └── user-menu.blade.php
└── theme-toggle.blade.php    # Alternador dark/light mode
```

### 5.2 Conceitos Importantes dos Componentes Blade

#### 5.2.1 Diretiva `@props`

Define as propriedades que o componente aceita com valores padrão:

```php
@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
])
```

#### 5.2.2 Attribute Bag com `$attributes`

Permite passar atributos HTML extras que não foram definidos em `@props`:

```php
<button {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
```

#### 5.2.3 Slots Nomeados

Permitem passar conteúdo para áreas específicas do componente:

```blade
<x-card>
    <x-slot:header>Título do Card</x-slot:header>
    
    Conteúdo principal aqui
    
    <x-slot:footer>
        <x-button>Salvar</x-button>
    </x-slot:footer>
</x-card>
```

---

### 5.3 Componente: Button

O botão mais completo do design system, com múltiplas variantes e estados.

**Props principais:**
| Prop | Tipo | Default | Descrição |
|------|------|---------|-----------|
| `variant` | string | `'primary'` | `primary`, `secondary`, `tertiary`, `danger`, `outline`, `ghost` |
| `size` | string | `'md'` | `xs`, `sm`, `md`, `lg`, `xl` |
| `href` | string | `null` | Se definido, renderiza como `<a>` ao invés de `<button>` |
| `disabled` | bool | `false` | Desabilita o botão |
| `loading` | bool | `false` | Mostra spinner de carregamento |
| `iconLeft` | slot | `null` | Ícone à esquerda |
| `iconRight` | slot | `null` | Ícone à direita |
| `fullWidth` | bool | `false` | Ocupa 100% da largura |

**Exemplos de uso:**

```blade
{{-- Botão primário simples --}}
<x-button>Salvar</x-button>

{{-- Botão com variante e tamanho --}}
<x-button variant="danger" size="sm">Excluir</x-button>

{{-- Botão como link --}}
<x-button href="/dashboard" variant="outline">Voltar</x-button>

{{-- Botão com loading (útil com Livewire) --}}
<x-button loading>Processando...</x-button>

{{-- Botão com ícone --}}
<x-button>
    <x-slot:iconLeft>
        <svg>...</svg>
    </x-slot:iconLeft>
    Download
</x-button>
```

---

### 5.4 Componente: Card

Container com header, body e footer opcionais.

**Props principais:**
| Prop | Tipo | Default | Descrição |
|------|------|---------|-----------|
| `variant` | string | `'default'` | `default`, `primary`, `secondary`, `transparent` |
| `padding` | string | `'default'` | `none`, `sm`, `default`, `md`, `lg`, `xl` |
| `shadow` | bool | `true` | Mostra sombra |
| `border` | bool | `true` | Mostra borda |
| `hover` | bool | `false` | Efeito hover com elevação |
| `interactive` | bool | `false` | Estilo clicável |

**Exemplo de uso:**

```blade
<x-card>
    <x-slot:header>
        <h3 class="text-lg font-semibold">Título</h3>
    </x-slot:header>

    <p>Conteúdo do card...</p>

    <x-slot:footer>
        <x-button variant="secondary">Cancelar</x-button>
        <x-button>Confirmar</x-button>
    </x-slot:footer>
</x-card>
```

**Subcomponentes disponíveis:**
- `<x-card.header>` - Com prop `divided` para linha separadora
- `<x-card.body>` - Corpo do card
- `<x-card.footer>` - Com prop `align` (`left`, `center`, `right`, `between`)

---

### 5.5 Componente: Modal

Modal com suporte a Alpine.js para abrir/fechar.

**Props principais:**
| Prop | Tipo | Default | Descrição |
|------|------|---------|-----------|
| `name` | string | `null` | Nome para abrir via evento Alpine |
| `show` | bool | `false` | Estado inicial |
| `maxWidth` | string | `'md'` | `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `full` |
| `closeable` | bool | `true` | Mostra botão de fechar |
| `closeOnClickOutside` | bool | `true` | Fecha ao clicar fora |
| `closeOnEscape` | bool | `true` | Fecha com tecla ESC |

**Ponto importante:** O modal pode ser controlado via:
1. **`wire:model`** - Integração com Livewire
2. **Eventos Alpine** - `$dispatch('open-modal', 'nome-do-modal')`

**Exemplo de uso:**

```blade
{{-- Botão para abrir --}}
<x-button @click="$dispatch('open-modal', 'confirmar-exclusao')">
    Excluir
</x-button>

{{-- Modal --}}
<x-modal name="confirmar-exclusao" maxWidth="sm">
    <x-slot:header>
        <h3 class="text-lg font-semibold">Confirmar Exclusão</h3>
    </x-slot:header>

    <p>Tem certeza que deseja excluir este item?</p>

    <x-slot:footer>
        <x-button variant="secondary" @click="$dispatch('close-modal', 'confirmar-exclusao')">
            Cancelar
        </x-button>
        <x-button variant="danger">Excluir</x-button>
    </x-slot:footer>
</x-modal>
```

---

### 5.6 Componentes de Formulário

Todos os componentes de formulário possuem:
- Integração automática com **validação do Laravel** (`$errors`)
- Suporte a **error bags** customizados
- **Label**, **hint** e **error** integrados

#### 5.6.1 Input

```blade
<x-form.input
    name="email"
    type="email"
    label="E-mail"
    placeholder="seu@email.com"
    hint="Usaremos para contato"
    required
/>
```

**Props importantes:**
- `iconLeft` / `iconRight` - Ícones dentro do input
- `prefix` / `suffix` - Texto fixo (ex: "R$", "@")
- `bag` - Error bag para validação (default: `'default'`)

#### 5.6.2 Select

```blade
<x-form.select
    name="status"
    label="Status"
    :options="['active' => 'Ativo', 'inactive' => 'Inativo']"
    placeholder="Selecione..."
/>

{{-- Ou com options manuais --}}
<x-form.select name="categoria" label="Categoria">
    <option value="1">Categoria 1</option>
    <option value="2">Categoria 2</option>
</x-form.select>
```

#### 5.6.3 Textarea

```blade
<x-form.textarea
    name="description"
    label="Descrição"
    rows="5"
    resize="vertical"
/>
```

#### 5.6.4 Checkbox

```blade
<x-form.checkbox
    name="terms"
    label="Aceito os termos de uso"
    description="Leia os termos antes de aceitar"
/>
```

---

### 5.7 Componente: Alert

Para mensagens de feedback ao usuário.

**Props principais:**
| Prop | Tipo | Default | Descrição |
|------|------|---------|-----------|
| `variant` | string | `'info'` | `success`, `error`, `warning`, `info`, `default` |
| `dismissible` | bool | `false` | Pode ser fechado |
| `icon` | bool | `true` | Mostra ícone |
| `bordered` | bool | `false` | Adiciona borda |

**Exemplo:**

```blade
<x-alert variant="success" dismissible>
    <x-slot:title>Sucesso!</x-slot:title>
    Seu registro foi salvo com sucesso.
</x-alert>

<x-alert variant="error">
    Ocorreu um erro ao processar sua solicitação.
</x-alert>
```

---

### 5.8 Componente: Table

Tabela responsiva com suporte a ordenação.

**Estrutura:**

```blade
<x-table>
    <x-table.head>
        <x-table.row>
            <x-table.header>Nome</x-table.header>
            <x-table.header sortable :sorted="$sortBy === 'email' ? $sortDirection : null">
                E-mail
            </x-table.header>
            <x-table.header align="right">Ações</x-table.header>
        </x-table.row>
    </x-table.head>

    <x-table.body>
        @foreach($users as $user)
            <x-table.row>
                <x-table.cell>{{ $user->name }}</x-table.cell>
                <x-table.cell>{{ $user->email }}</x-table.cell>
                <x-table.cell align="right">
                    <x-button size="xs" variant="ghost">Editar</x-button>
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table.body>
</x-table>
```

**Ponto importante:** O `<x-table.header>` aceita `sortable` e `sorted` para indicadores visuais de ordenação.

---

### 5.9 Componente: Sidebar

Navegação lateral completa com menu, grupos e user menu.

**Estrutura:**

```blade
<x-sidebar>
    <x-slot:header>
        <img src="/logo.svg" alt="Logo" class="h-8">
    </x-slot:header>

    <x-sidebar.menu-item href="/dashboard" :active="request()->is('dashboard')">
        <x-slot:icon>
            <svg>...</svg>
        </x-slot:icon>
        Dashboard
    </x-sidebar.menu-item>

    <x-sidebar.menu-group label="Configurações">
        <x-slot:icon>
            <svg>...</svg>
        </x-slot:icon>
        <x-sidebar.menu-item href="/settings/profile">Perfil</x-sidebar.menu-item>
        <x-sidebar.menu-item href="/settings/security">Segurança</x-sidebar.menu-item>
    </x-sidebar.menu-group>

    <x-slot:footer>
        <x-sidebar.user-menu />
    </x-slot:footer>
</x-sidebar>
```

**Pontos importantes:**
- `<x-sidebar.menu-item>` detecta automaticamente se está ativo via `request()->url()`
- `<x-sidebar.menu-group>` usa Alpine.js para expandir/colapsar
- `<x-sidebar.user-menu>` mostra iniciais do usuário e dropdown com logout

---

### 5.10 Componente: Theme Toggle

Alternador de tema claro/escuro usando Alpine.js Store.

```blade
<x-theme-toggle />

{{-- Com label --}}
<x-theme-toggle showLabel />
```

**Ponto importante:** Requer configuração do Alpine Store `darkMode` no layout.

---

### 5.11 Resumo: Componentes Blade vs Livewire

| Característica | Componentes Blade | Componentes Livewire |
|---------------|-------------------|---------------------|
| Estado | Sem estado (stateless) | Com estado no servidor |
| Reatividade | Via Alpine.js (cliente) | Via Livewire (servidor) |
| Quando usar | UI pura, apresentação | Formulários, interações com dados |
| Performance | Mais leve | Mais pesado (roundtrips) |

**No projeto:** Os componentes do Design System são **Blade puro** para máxima performance. Os componentes **Livewire** são usados para páginas/features que precisam de estado e interação com o backend.

---

## Parte 6: Fluxo de Cadastro e Login

### 6.1 Visão Geral da Arquitetura

O projeto usa **Livewire Volt** com **Full Page Components**. Isso significa que cada página é um arquivo `.blade.php` que contém tanto a lógica PHP quanto o template HTML.

```
Estrutura de Arquivos:
├── routes/web.php                         # Rotas
├── resources/views/layouts/auth.blade.php # Layout de autenticação
├── resources/views/pages/auth/
│   ├── ⚡login.blade.php                  # Página de login (Volt)
│   └── ⚡register.blade.php               # Página de cadastro (Volt)
└── app/Livewire/Forms/
    ├── LoginForm.php                      # Form Object de login
    └── RegisterForm.php                   # Form Object de cadastro
```

**O símbolo ⚡ no nome do arquivo** indica que é um componente Volt (Livewire inline).

---

### 6.2 Rotas de Autenticação

```php
// routes/web.php

// Rotas para usuários NÃO autenticados (guest)
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
});

// Rota de logout (POST para segurança)
Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// Rotas protegidas (requerem autenticação)
Route::livewire('/', 'pages::home')->name('home')->middleware('auth');
```

**Pontos importantes:**
- `Route::livewire()` é um helper do Livewire v4 para Full Page Components
- `middleware('guest')` redireciona usuários logados para home
- `middleware('auth')` redireciona usuários não logados para login

---

### 6.3 Fluxo de Cadastro (Register)

#### Passo 1: Usuário acessa `/register`

```
┌─────────────────────────────────────────────────────────────┐
│                    TELA DE CADASTRO                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   Nome completo:     [________________________]             │
│                                                             │
│   Email:             [________________________]             │
│                                                             │
│   Telefone:          [(99) 99999-9999________]             │
│                                                             │
│   Senha:             [••••••••________________]             │
│   (Mínimo 8 caracteres)                                     │
│                                                             │
│   Confirmar senha:   [••••••••________________]             │
│                                                             │
│   [✓] Aceito os termos de uso                               │
│                                                             │
│   [        Criar conta        ]                             │
│                                                             │
│   ─────────── Ou cadastre-se com ───────────                │
│                                                             │
│   [ Google ]  [ GitHub ]                                    │
│                                                             │
│   Já tem uma conta? Entrar                                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### Passo 2: Form Object - RegisterForm.php

```php
<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class RegisterForm extends Form
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Regras de validação
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|min:3|max:255',
            'password' => [
                'required',
                'confirmed',  // Valida password_confirmation
                Password::min(8)
                    ->letters()      // Pelo menos uma letra
                    ->mixedCase()    // Maiúsculas e minúsculas
                    ->numbers()      // Pelo menos um número
                    ->symbols()      // Pelo menos um símbolo
                    ->uncompromised() // Verifica vazamentos
            ]
        ];
    }

    public function register()
    {
        $this->validate();

        // Cria o usuário
        $user = User::create($this->all());

        // Já faz login automático
        auth()->login($user);
    }
}
```

#### Passo 3: Página Volt - ⚡register.blade.php

```php
<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Livewire\Forms\RegisterForm;

new
#[Layout('layouts.auth')]  // Define o layout
#[Title('Criar Conta')]    // Define o título da página
class extends Component
{
    public RegisterForm $form;  // Injeta o Form Object

    public function register(): void
    {
        $this->form->register();  // Chama o método do Form

        session()->regenerate();  // Segurança: regenera sessão

        $this->redirect(
            route('home'),
            navigate: true  // SPA navigation
        );
    }
};
?>

{{-- Template HTML abaixo --}}
<div>
    <form wire:submit="register">
        <x-form.input wire:model="form.name" label="Nome" />
        <x-form.input wire:model="form.email" label="Email" />
        {{-- ... outros campos ... --}}
        <x-button type="submit">Criar conta</x-button>
    </form>
</div>
```

#### Passo 4: Fluxo Completo

```
1. Usuário preenche formulário
           │
           ▼
2. Clica em "Criar conta"
           │
           ▼
3. wire:submit="register" dispara
           │
           ▼
4. Livewire envia dados para o servidor
           │
           ▼
5. RegisterForm->validate() executa
           │
     ┌─────┴─────┐
     │           │
   ERRO       SUCESSO
     │           │
     ▼           ▼
6a. Retorna    6b. User::create()
    erros          │
     │             ▼
     ▼         7. auth()->login($user)
Exibe erros        │
nos campos         ▼
               8. session()->regenerate()
                   │
                   ▼
               9. redirect('/') com navigate:true
                   │
                   ▼
              10. Usuário na Home (logado)
```

---

### 6.4 Fluxo de Login

#### Passo 1: Usuário acessa `/login`

```
┌─────────────────────────────────────────────────────────────┐
│                    TELA DE LOGIN                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   Email:             [________________________]             │
│                                                             │
│   Senha:             [••••••••]    Esqueceu a senha?        │
│                                                             │
│   [✓] Lembrar de mim                                        │
│                                                             │
│   [          Entrar          ]                              │
│                                                             │
│   Não tem uma conta? Criar conta                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### Passo 2: Form Object - LoginForm.php

```php
<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginForm extends Form
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate()
    {
        $this->validate();

        // Proteção contra brute force
        $this->ensureIsNotRateLimited();

        // Tenta autenticar
        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            // Falhou: incrementa contador de tentativas
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        // Sucesso: limpa contador
        RateLimiter::clear($this->throttleKey());
    }

    // Verifica se não excedeu limite de tentativas
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;  // OK, pode tentar
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    // Chave única por email + IP
    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->email) . '|' . request()->ip()
        );
    }
}
```

#### Passo 3: Página Volt - ⚡login.blade.php

```php
<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Livewire\Forms\LoginForm;

new
#[Layout('layouts.auth')]
#[Title('Login')]
class extends Component
{
    public LoginForm $form;

    public function login()
    {
        $this->form->authenticate();

        session()->regenerate();

        // Redireciona para URL pretendida ou home
        return $this->redirect(
            session('url.intended', route('home')),
            navigate: true
        );
    }
};
?>

<div>
    <form wire:submit.prevent="login">
        <x-form.input
            wire:model="form.email"
            type="email"
            label="Email"
        />
        <x-form.input
            wire:model="form.password"
            type="password"
            label="Senha"
        />
        <x-form.checkbox
            wire:model="form.remember"
            label="Lembrar de mim"
        />
        <x-button type="submit">Entrar</x-button>
    </form>
</div>
```

#### Passo 4: Fluxo Completo

```
1. Usuário preenche email e senha
           │
           ▼
2. Clica em "Entrar"
           │
           ▼
3. wire:submit.prevent="login"
           │
           ▼
4. Livewire envia para servidor
           │
           ▼
5. LoginForm->validate()
           │
     ┌─────┴─────┐
   ERRO       SUCESSO
     │           │
     ▼           ▼
  Exibe erro  6. ensureIsNotRateLimited()
                 │
           ┌─────┴─────┐
         BLOQUEADO   OK
           │           │
           ▼           ▼
       "Aguarde     7. Auth::attempt()
        X segundos"    │
                 ┌─────┴─────┐
               FALHOU     SUCESSO
                 │           │
                 ▼           ▼
            RateLimiter   RateLimiter
              ::hit()       ::clear()
                 │           │
                 ▼           ▼
            "Credenciais  8. session()->regenerate()
             inválidas"      │
                             ▼
                         9. redirect(url.intended)
                             │
                             ▼
                        10. Usuário na página desejada
```

---

### 6.5 Layout de Autenticação

O layout `layouts/auth.blade.php` fornece uma estrutura visual com:

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  ┌─────────────────────────┐  ┌─────────────────────────────────┐  │
│  │                         │  │                                 │  │
│  │    LADO ESQUERDO        │  │      LADO DIREITO               │  │
│  │    (Desktop only)       │  │      (Formulário)               │  │
│  │                         │  │                                 │  │
│  │  ┌─────────────────┐    │  │    [Toggle Dark Mode]           │  │
│  │  │ Logo            │    │  │                                 │  │
│  │  └─────────────────┘    │  │    Logo (mobile only)           │  │
│  │                         │  │                                 │  │
│  │  Bem-vindo de volta!    │  │    {{ $slot }}                  │  │
│  │                         │  │    (conteúdo da página)         │  │
│  │  Texto motivacional...  │  │                                 │  │
│  │                         │  │                                 │  │
│  │  ✓ Rápido e Seguro      │  │                                 │  │
│  │  ✓ Acesso 24/7          │  │                                 │  │
│  │                         │  │                                 │  │
│  │  © 2026 App Name        │  │                                 │  │
│  │                         │  │                                 │  │
│  └─────────────────────────┘  └─────────────────────────────────┘  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

### 6.6 Segurança Implementada

| Recurso | Descrição |
|---------|-----------|
| **Rate Limiting** | Máximo 5 tentativas de login por email+IP |
| **Password Rules** | Mínimo 8 chars, maiúsculas, minúsculas, números, símbolos |
| **Uncompromised** | Verifica se senha não está em vazamentos conhecidos |
| **Session Regenerate** | Regenera ID da sessão após login (previne fixation) |
| **CSRF** | Protegido automaticamente pelo Livewire |
| **Middleware Guest** | Impede acesso às páginas de auth se já logado |

---

### 6.7 Resumo: Form Objects no Livewire

Os **Form Objects** (`Livewire\Form`) separam a lógica de formulário do componente:

```php
// Sem Form Object (tudo no componente)
class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected $rules = [...];

    public function login() { ... }
}

// Com Form Object (separado e reutilizável)
class Login extends Component
{
    public LoginForm $form;  // Injeta o Form

    public function login()
    {
        $this->form->authenticate();
    }
}
```

**Vantagens:**
- Código mais organizado
- Lógica de validação reutilizável
- Componente mais limpo
- Facilita testes

---

## Parte 7: Fluxo de Meta → Diagnóstico → IA

### 7.1 Visão Geral do Fluxo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FLUXO COMPLETO                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   1. HOME                    2. META                 3. DIAGNÓSTICO     │
│   ┌─────────────┐           ┌─────────────┐        ┌─────────────┐     │
│   │             │           │             │        │             │     │
│   │ [Nova Meta] │──────────►│ Detalhes    │───────►│ IA Analisa  │     │
│   │             │  Criar    │ da Meta     │ Criar  │ e Gera      │     │
│   │ Lista de    │           │             │ Diag.  │ Diagnóstico │     │
│   │ Metas       │           │ + Itens do  │        │             │     │
│   │             │           │ Diagnóstico │        │ Recomenda   │     │
│   └─────────────┘           └─────────────┘        │ Prioridades │     │
│                                                    └─────────────┘     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Arquivos envolvidos:**

```
├── resources/views/pages/
│   ├── ⚡home.blade.php              # Lista de metas
│   ├── goals/⚡index.blade.php       # Detalhes + Coleta diagnóstico
│   └── diagnosis/⚡index.blade.php   # Resultado com IA
├── app/Livewire/Forms/
│   ├── GoalForm.php                  # Criar/editar meta
│   └── DiagnosisForm.php             # Gerenciar itens diagnóstico
├── app/Services/
│   └── AgentDiagnosisServices.php    # Integração com IA
├── app/Enums/
│   ├── DiagnosisPillarEnum.php       # Pilares (Técnico, Estratégico, Comportamental)
│   └── DiagnosisItemTypeEnum.php     # Tipos (Domino bem, Preciso melhorar)
└── resources/views/prompts/
    └── diagnosis-system-prompt.blade.php  # Prompt da IA
```

---

### 7.2 Etapa 1: Criação da Meta (Home)

#### Tela Visual

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Bem-vindo ao Planner                                    [🌙]           │
│  Este é o layout da área logada com menu lateral.                       │
│                                                          [+ Nova Meta]  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐         │
│  │ Meta 1          │  │ Meta 2          │  │ Meta 3          │         │
│  │                 │  │                 │  │                 │         │
│  │ Descrição...    │  │ Descrição...    │  │ Descrição...    │         │
│  │                 │  │                 │  │                 │         │
│  │ [Acompanhar]    │  │ [Acompanhar]    │  │ [Acompanhar]    │         │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### Modal de Criação

```
┌─────────────────────────────────────────┐
│  Criar nova meta                    [X] │
├─────────────────────────────────────────┤
│                                         │
│  Nome da meta:                          │
│  [_________________________________]    │
│                                         │
│  Prazo:                                 │
│  [____/____/________]                   │
│                                         │
│  Como você quer ser reconhecido:        │
│  [_________________________________]    │
│  [_________________________________]    │
│  [_________________________________]    │
│                                         │
├─────────────────────────────────────────┤
│              [Cancelar]  [Confirmar]    │
└─────────────────────────────────────────┘
```

#### GoalForm.php

```php
<?php

namespace App\Livewire\Forms;

use App\Enums\GoalSituationEnum;
use App\Models\Goal;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GoalForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('required|date|after:today')]
    public string $deadline = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $description = '';

    public function store(): Goal
    {
        $this->validate();

        return Goal::create([
            ...$this->only(['name', 'deadline', 'description']),
            'goal_situation_id' => GoalSituationEnum::InProgress,
            'user_id' => auth()->id(),
        ]);
    }
}
```

#### Página Home (resumo)

```php
<?php
// resources/views/pages/⚡home.blade.php

new class extends Component {
    public GoalForm $form;
    public bool $showGoalModal = false;
    public $goals;

    public function mount()
    {
        $this->loadGoals();
    }

    public function loadGoals()
    {
        $this->goals = Goal::where('user_id', auth()->id())->get();
    }

    public function newGoal()
    {
        $goal = $this->form->store();
        $this->loadGoals();
    }
};
```

---

### 7.3 Etapa 2: Coleta de Informações do Diagnóstico

#### Conceito dos Pilares e Tipos

O diagnóstico é organizado em uma **matriz 3x2**:

```
                    │  Domino Bem    │  Preciso Melhorar
────────────────────┼────────────────┼───────────────────
Pilar Técnico       │  [items...]    │  [items...]
Pilar Estratégico   │  [items...]    │  [items...]
Pilar Comportamental│  [items...]    │  [items...]
```

#### Enums

```php
// app/Enums/DiagnosisPillarEnum.php
enum DiagnosisPillarEnum: int
{
    case Technical = 1;    // Habilidades técnicas
    case Strategic = 2;    // Visão estratégica
    case Behavioral = 3;   // Soft skills

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical',
            self::Strategic => 'Strategic',
            self::Behavioral => 'Behavioral',
        };
    }
}

// app/Enums/DiagnosisItemTypeEnum.php
enum DiagnosisItemTypeEnum: int
{
    case DoingWell = 1;      // Pontos fortes
    case NeedToImprove = 2;  // Pontos fracos

    public function label(): string
    {
        return match ($this) {
            self::DoingWell => 'DoingWell',
            self::NeedToImprove => 'NeedToImprove',
        };
    }
}
```

#### Tela de Coleta (Modal)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Criar novo Diagnóstico                                             [X] │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  DOMINO BEM                                                             │
│  ┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐│
│  │ Pilar Técnico       │ │ Pilar Estratégico   │ │ Pilar Comportamental││
│  │                     │ │                     │ │                     ││
│  │ [____________][Add] │ │ [____________][Add] │ │ [____________][Add] ││
│  │                     │ │                     │ │                     ││
│  │ • PHP avançado  [X] │ │ • Visão de neg.[X] │ │ • Liderança    [X] ││
│  │ • Laravel       [X] │ │ • Planejamento [X] │ │ • Comunicação  [X] ││
│  └─────────────────────┘ └─────────────────────┘ └─────────────────────┘│
│                                                                         │
│  PRECISO MELHORAR                                                       │
│  ┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐│
│  │ Pilar Técnico       │ │ Pilar Estratégico   │ │ Pilar Comportamental││
│  │                     │ │                     │ │                     ││
│  │ [____________][Add] │ │ [____________][Add] │ │ [____________][Add] ││
│  │                     │ │                     │ │                     ││
│  │ • DevOps        [X] │ │ • Networking   [X] │ │ • Delegar      [X] ││
│  │ • Cloud         [X] │ │ • Inglês       [X] │ │ • Paciência    [X] ││
│  └─────────────────────┘ └─────────────────────┘ └─────────────────────┘│
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                      [Cancelar]  [Confirmar]            │
└─────────────────────────────────────────────────────────────────────────┘
```

#### DiagnosisForm.php

```php
<?php

namespace App\Livewire\Forms;

use App\Enums\DiagnosisItemTypeEnum;
use App\Enums\DiagnosisPillarEnum;
use App\Models\Diagnosis;
use App\Models\DiagnosisItem;
use App\Models\Goal;
use Livewire\Form;

class DiagnosisForm extends Form
{
    // Array dinâmico: input[Pilar][Tipo] = valor
    public array $input;

    public function rules()
    {
        return [
            'input.Technical.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Strategic.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Behavioral.DoingWell' => 'nullable|string|min:3|max:255',
            'input.Technical.NeedToImprove' => 'nullable|string|min:3|max:255',
            'input.Strategic.NeedToImprove' => 'nullable|string|min:3|max:255',
            'input.Behavioral.NeedToImprove' => 'nullable|string|min:3|max:255',
        ];
    }

    public function createDiagnosis(Goal $goal)
    {
        return $goal->diagnoses()->create();
    }

    public function addItem(string $pillar, string $type, Diagnosis $diagnosis)
    {
        $this->validate();

        // Converte string para valor do Enum
        $diagnosis->items()->create([
            'diagnosis_pillar_id' => constant(DiagnosisPillarEnum::class . '::' . $pillar)->value,
            'diagnosis_item_type_id' => constant(DiagnosisItemTypeEnum::class . '::' . $type)->value,
            'description' => $this->input[$pillar][$type],
        ]);

        // Limpa o campo após adicionar
        unset($this->input[$pillar][$type]);
    }

    public function removeItem($item_id)
    {
        DiagnosisItem::find($item_id)->delete();
    }
}
```

#### Página de Meta com Coleta (resumo)

```php
<?php
// resources/views/pages/goals/⚡index.blade.php

new class extends Component {
    public DiagnosisForm $form;
    public Goal $goal;
    public bool $showDiagnosisModal;
    public ?Diagnosis $diagnosis;

    public function mount(Goal $goal)
    {
        // Verifica autorização
        abort_if($goal->user_id !== auth()->id(), 403);

        $this->goal = $goal;

        // Busca diagnóstico em criação ou cria novo
        $this->diagnosis = $this->goal->diagnoses
            ->where('diagnosis_status_id', 1)
            ->first();

        if (!$this->diagnosis) {
            $this->diagnosis = $this->form->createDiagnosis($this->goal);
        }
    }

    public function addItem($pillar, $type)
    {
        $this->form->addItem($pillar, $type, $this->diagnosis);
    }

    public function confirmDiagnosis()
    {
        // Muda status para "in-progress"
        $this->diagnosis->diagnosis_status_id = 2;
        $this->diagnosis->save();

        // Redireciona para tela de diagnóstico com IA
        $this->redirect(
            route('diagnosis.index', $this->diagnosis->id),
            navigate: true
        );
    }
};
```

---

### 7.4 Etapa 3: Geração do Diagnóstico com IA

#### Fluxo da IA

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     FLUXO DE INTEGRAÇÃO COM IA                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1. Usuário confirma diagnóstico                                        │
│           │                                                             │
│           ▼                                                             │
│  2. Redireciona para /diagnosis/{id}                                    │
│           │                                                             │
│           ▼                                                             │
│  3. mount() verifica se já tem descrição                                │
│           │                                                             │
│     ┌─────┴─────┐                                                       │
│   TEM          NÃO TEM                                                  │
│     │              │                                                    │
│     ▼              ▼                                                    │
│  Mostra        4. dispatch('generateDiagnostic')                        │
│  resultado         │                                                    │
│                    ▼                                                    │
│               5. AgentDiagnosisServices->create()                       │
│                    │                                                    │
│                    ▼                                                    │
│               6. Prism (OpenAI) processa                                │
│                    │                                                    │
│                    ▼                                                    │
│               7. Retorna:                                               │
│                  - diagnosis (texto markdown)                           │
│                  - diagnosis_item_ids (3 prioridades)                   │
│                    │                                                    │
│                    ▼                                                    │
│               8. Salva no banco e atualiza tela                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

#### Serviço de IA - AgentDiagnosisServices.php

```php
<?php

namespace App\Services;

use App\Models\Diagnosis;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class AgentDiagnosisServices
{
    /**
     * Define o schema de saída esperado da IA
     * (Structured Output)
     */
    public function diagnosisOutputSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'questions',
            description: 'List of questions',
            properties: [
                new StringSchema(
                    'diagnosis',
                    'O diagnostico completo em texto corrido formato em markdown'
                ),
                new ArraySchema(
                    name: 'diagnosis_item_ids',
                    description: 'Ids de 3 diagnosticos marcados para serem trabalhados',
                    items: new StringSchema(
                        name: 'diagnosis_item_id',
                        description: 'O Id numerico do item que precisa ser melhorado',
                    )
                )
            ],
            requiredFields: ['diagnosis', 'diagnosis_item_ids']
        );
    }

    /**
     * Gera o diagnóstico usando IA
     */
    public function create(Diagnosis $diagnosis)
    {
        $response = Prism::structured()
            ->using(Provider::OpenAI, 'gpt-5-mini')
            ->withSchema($this->diagnosisOutputSchema())
            ->withSystemPrompt(view('prompts.diagnosis-system-prompt'))
            ->withPrompt($diagnosis->load('goal', 'items.type', 'items.pillar')->toJson())
            ->withClientOptions(['timeout' => 120])
            ->asStructured();

        return $response;
    }
}
```

**Pontos importantes:**

1. **Prism** - Pacote Laravel para integração com LLMs
2. **Structured Output** - Define schema para resposta estruturada (JSON)
3. **System Prompt** - Vem de uma view Blade (permite usar variáveis)
4. **User Prompt** - O próprio diagnóstico serializado como JSON

#### Prompt do Sistema

```markdown
// resources/views/prompts/diagnosis-system-prompt.blade.php

# Goal
Realizar um diagnóstico estratégico de carreira do usuário, identificando
o gap entre sua posição atual e sua meta profissional, e recomendar o
ponto fraco prioritário em cada pilar (técnico, estratégico e comportamental)
para ação imediata.

# Return format
O assistente deve estruturar a resposta em 2 seções:

1. **Diagnóstico Inicial** (texto corrido, 3-4 parágrafos):
   Análise clara do gap entre posição atual e meta...

2. **Prioridades de Desenvolvimento** (formato estruturado com ID):
   Exatamente 1 ponto fraco de cada pilar...

# Warnings
- Não confundir pontos fortes com justificativa para ignorar fracos críticos
- Evitar recomendações genéricas
- Não assumir que todos os 3 pontos fracos têm igual urgência
- Se a meta for pouco clara ou o prazo irrealista, sinalizar isso

# Context
O usuário fornecerá:
- **Meta**: Nome da posição/objetivo, prazo, descrição
- **Pontos Fortes**: competências em cada pilar
- **Pontos Fracos**: limitações em cada pilar
```

#### Página de Diagnóstico (resumo)

```php
<?php
// resources/views/pages/diagnosis/⚡index.blade.php

new class extends Component {
    public Diagnosis $diagnosis;
    private AgentDiagnosisServices $agentDiagnosisServices;

    // Injeta o serviço via boot (chamado a cada request)
    public function boot(AgentDiagnosisServices $agentDiagnosisServices)
    {
        $this->agentDiagnosisServices = $agentDiagnosisServices;
    }

    public function mount(Diagnosis $diagnosis)
    {
        $this->diagnosis = $diagnosis->load('goal', 'items.type', 'items.pillar');

        // Verifica autorização
        abort_if($this->diagnosis->goal->user_id !== auth()->id(), 403);

        // Se não tem descrição, dispara evento para gerar
        if (!$this->diagnosis->description) {
            $this->dispatch('generateDiagnostic');
        }
    }

    // Listener do evento
    #[On('generateDiagnostic')]
    public function generateDiagnostic()
    {
        // Chama o serviço de IA
        $response = $this->agentDiagnosisServices->create($this->diagnosis);

        // Salva o texto do diagnóstico
        $this->diagnosis->description = $response->structured["diagnosis"];
        $this->diagnosis->save();

        // Limpa seleções anteriores
        $this->diagnosis->items()->update(['agent_selected_at' => null]);

        // Marca os 3 itens priorizados pela IA
        $this->diagnosis->items()
            ->whereIn('id', $response->structured['diagnosis_item_ids'])
            ->update(['agent_selected_at' => now()]);

        $this->diagnosis->refresh();
    }

    // Permite usuário selecionar um item manualmente
    public function selectItem($item_id)
    {
        $item = $this->diagnosis->items()->where('id', $item_id)->first();

        // Remove seleção anterior do mesmo pilar
        $this->diagnosis->items()
            ->where('diagnosis_pillar_id', $item->diagnosis_pillar_id)
            ->update(['user_selected_at' => null]);

        // Marca o novo item
        $item->update(['user_selected_at' => now()]);
    }
};
```

#### Tela de Resultado

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Meta: Tech Lead                                                        │
│  Meta criada: 22/01/2026 14:30                                          │
│  Diagnóstico realizado: 22/01/2026 15:00                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  DIAGNÓSTICO INICIAL                                                    │
│  ─────────────────────────────────────────────────────────────────────  │
│  [Texto gerado pela IA em Markdown renderizado...]                      │
│                                                                         │
│  Análise do gap entre sua posição atual e a meta de Tech Lead...        │
│  Considerando seus pontos fortes em PHP e Laravel, você tem uma base... │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│  DOMINO BEM                                                             │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐           │
│  │ Técnico         │ │ Estratégico     │ │ Comportamental  │           │
│  │ • PHP avançado  │ │ • Visão de neg. │ │ • Liderança     │           │
│  │ • Laravel       │ │ • Planejamento  │ │ • Comunicação   │           │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘           │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│  PRECISO MELHORAR                                                       │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐           │
│  │ Técnico         │ │ Estratégico     │ │ Comportamental  │           │
│  │ ┌─────────────┐ │ │ • Networking    │ │ • Delegar       │           │
│  │ │🔴 DevOps   │←┤ │ • Inglês        │ │ ┌─────────────┐ │           │
│  │ └─────────────┘ │ │ ┌─────────────┐ │ │ │🔴 Paciência│←┤           │
│  │ • Cloud         │ │ │🔴 Networking│←┤ │ └─────────────┘ │           │
│  └─────────────────┘ │ └─────────────┘ │ └─────────────────┘           │
│                      └─────────────────┘                                │
│                                                                         │
│  🔴 = Priorizado pela IA     [✓] = Selecionar manualmente              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

### 7.5 Fluxo de Status do Diagnóstico

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     STATUS DO DIAGNÓSTICO                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   creating (1)         in-progress (2)        completed (3)             │
│   ┌───────────┐        ┌───────────┐         ┌───────────┐             │
│   │           │        │           │         │           │             │
│   │ Coletando │───────►│ IA gerou  │────────►│ Plano de  │             │
│   │ dados do  │ Confir-│ diagnós-  │ (futuro)│ ação      │             │
│   │ usuário   │ mar    │ tico      │         │ criado    │             │
│   │           │        │           │         │           │             │
│   └───────────┘        └───────────┘         └───────────┘             │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

### 7.6 Resumo: Integração IA com Prism

| Componente | Descrição |
|------------|-----------|
| **Prism** | Pacote Laravel para LLMs (OpenAI, Anthropic, etc) |
| **Provider** | `Provider::OpenAI` com modelo `gpt-5-mini` |
| **Structured Output** | Schema define formato exato da resposta |
| **System Prompt** | View Blade com instruções para a IA |
| **User Prompt** | Dados do diagnóstico em JSON |
| **Timeout** | 120 segundos para operações longas |

**Exemplo de resposta estruturada:**

```json
{
    "diagnosis": "## Diagnóstico Inicial\n\nAnalisando sua meta de...",
    "diagnosis_item_ids": ["5", "8", "12"]
}
```

---

### 7.7 Diagrama de Sequência Completo

```
┌──────┐     ┌──────┐     ┌──────────┐     ┌─────────┐     ┌────────┐
│ User │     │ Home │     │GoalIndex │     │DiagIndex│     │ OpenAI │
└──┬───┘     └──┬───┘     └────┬─────┘     └────┬────┘     └───┬────┘
   │            │              │                │              │
   │ Criar Meta │              │                │              │
   │───────────►│              │                │              │
   │            │              │                │              │
   │◄───────────│              │                │              │
   │ Modal abre │              │                │              │
   │            │              │                │              │
   │ Preenche   │              │                │              │
   │───────────►│              │                │              │
   │            │ GoalForm     │                │              │
   │            │ ->store()    │                │              │
   │            │──────────────│                │              │
   │            │              │                │              │
   │ Acompanhar │              │                │              │
   │───────────────────────────►                │              │
   │            │              │                │              │
   │            │              │ Preenche itens │              │
   │            │              │◄───────────────│              │
   │            │              │                │              │
   │            │              │ Confirmar      │              │
   │            │              │───────────────►│              │
   │            │              │                │              │
   │            │              │                │ Prism call   │
   │            │              │                │─────────────►│
   │            │              │                │              │
   │            │              │                │◄─────────────│
   │            │              │                │ JSON struct  │
   │            │              │                │              │
   │◄───────────────────────────────────────────│              │
   │            │    Resultado do diagnóstico   │              │
   │            │              │                │              │
```
