<?php

namespace App\Livewire;

use App\Livewire\Traits\WithTableState;
use App\Models\Attribute as ProductAttribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CatalogManager extends Component
{
    use WithTableState;

    public string $type;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $parentId = null;

    public bool $isActive = true;

    public bool $isDefault = false;

    public string $value = '';

    public string $newValue = '';

    public string $abbreviation = '';

    public function mount(string $type): void
    {
        abort_unless(array_key_exists($type, $this->definitions()), 404);
        $this->type = $type;
    }

    protected function definitions(): array
    {
        return [
            'categories' => [Category::class, 'Categorías', 'Categoría'],
            'brands' => [Brand::class, 'Marcas', 'Marca'],
            'units' => [Unit::class, 'Unidades', 'Unidad'],
            'locations' => [Location::class, 'Ubicaciones', 'Ubicación'],
            'attributes' => [ProductAttribute::class, 'Atributos', 'Atributo'],
        ];
    }

    protected function modelClass(): string
    {
        return $this->definitions()[$this->type][0];
    }

    public function policyClass(): string
    {
        return $this->modelClass();
    }

    public function create(): void
    {
        Gate::authorize('create', $this->modelClass());
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = $this->modelClass()::findOrFail($id);
        Gate::authorize('update', $model);
        $this->editingId = $id;
        $this->name = $model->name;
        $this->parentId = $model->parent_id;
        $this->isActive = (bool) ($model->is_active ?? true);
        $this->isDefault = (bool) ($model->is_default ?? false);
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'isActive' => ['boolean']];
        if ($this->type === 'categories') {
            $rules['parentId'] = ['nullable', 'exists:categories,id'];
        }
        if ($this->type === 'units') {
            $rules['abbreviation'] = ['nullable', 'string'];
        }
        $this->validate($rules);
        $model = $this->editingId ? $this->modelClass()::findOrFail($this->editingId) : new ($this->modelClass());
        Gate::authorize($this->editingId ? 'update' : 'create', $model);
        $data = ['name' => $this->name, 'is_active' => $this->isActive];
        if ($this->type === 'categories') {
            $data['parent_id'] = $this->parentId;
        }
        if ($this->type === 'locations') {
            $data['is_default'] = $this->isDefault;
        }
        if ($this->type === 'units') {
            $data['abbreviation'] = $this->abbreviation !== '' ? $this->abbreviation : str($this->name)->lower()->substr(0, 3)->toString();
        }
        $model->fill($data)->save();
        $this->showModal = false;
        $this->dispatch('toast', message: $this->editingId ? 'Registro actualizado.' : 'Registro creado.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $model = $this->modelClass()::findOrFail($id);
        Gate::authorize('delete', $model);
        $model->delete();
        $this->dispatch('toast', message: 'Registro eliminado.');
    }

    public function addValue(int $attributeId): void
    {
        Gate::authorize('update', ProductAttribute::findOrFail($attributeId));
        $this->validate(['newValue' => ['required', 'string', 'max:255']]);
        ProductAttribute::findOrFail($attributeId)->values()->create(['value' => $this->newValue]);
        $this->newValue = '';
    }

    public function render()
    {
        $query = $this->modelClass()::query()->when($this->type === 'attributes', fn ($query) => $query->with('values'))->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'));
        $records = $query->orderBy($this->sortBy, $this->sortDirection)->paginate($this->perPage);

        return view('livewire.catalog-manager', ['records' => $records, 'title' => $this->definitions()[$this->type][1], 'singular' => $this->definitions()[$this->type][2], 'parents' => $this->type === 'categories' ? Category::query()->where('id', '!=', $this->editingId)->orderBy('name')->get() : collect()])
            ->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'parentId', 'newValue', 'abbreviation']);
        $this->isActive = true;
        $this->isDefault = false;
    }
}
