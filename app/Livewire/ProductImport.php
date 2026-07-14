<?php

namespace App\Livewire;

use App\Exports\ProductTemplateExport;
use App\Imports\ProductsImport;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ProductImport extends Component
{
    use WithFileUploads;

    public mixed $file = null;

    public ?array $result = null;

    public function downloadTemplate()
    {
        Gate::authorize('viewAny', Product::class);

        return Excel::download(new ProductTemplateExport, 'plantilla-productos.xlsx');
    }

    public function import(): void
    {
        Gate::authorize('create', Product::class);
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $import = app(ProductsImport::class);
        Excel::import($import, $this->file->getRealPath());
        $this->result = ['processed' => $import->processed, 'created' => $import->created, 'updated' => $import->updated, 'errors' => $import->errors];
        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.product-import')->layout('layouts.app');
    }
}
