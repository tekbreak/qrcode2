<?php

namespace App\Livewire\QrCodes;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryIndex extends Component
{
    public string $name = '';
    public string $color = 'blue';
    public ?int $editingId = null;
    public string $editingName = '';
    public string $editingColor = 'blue';

    public function create(): void
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('user_id', auth()->id()),
            ],
            'color' => 'required|string|in:' . implode(',', array_keys(Category::colorOptions())),
        ]);

        auth()->user()->categories()->create([
            'name' => trim($this->name),
            'color' => $this->color,
        ]);

        $this->reset('name');
        $this->color = 'blue';
        session()->flash('status', __('qr.category_created'));
    }

    public function startEdit(int $id): void
    {
        $category = auth()->user()->categories()->findOrFail($id);
        $this->editingId = $category->id;
        $this->editingName = $category->name;
        $this->editingColor = $category->color;
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingName');
        $this->editingColor = 'blue';
    }

    public function update(): void
    {
        $this->validate([
            'editingName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('user_id', auth()->id())->ignore($this->editingId),
            ],
            'editingColor' => 'required|string|in:' . implode(',', array_keys(Category::colorOptions())),
        ]);

        $category = auth()->user()->categories()->findOrFail($this->editingId);
        $category->update([
            'name' => trim($this->editingName),
            'color' => $this->editingColor,
        ]);

        $this->cancelEdit();
        session()->flash('status', __('qr.category_updated'));
    }

    public function delete(int $id): void
    {
        auth()->user()->categories()->findOrFail($id)->delete();
        session()->flash('status', __('qr.category_deleted'));
    }

    public function render()
    {
        return view('livewire.qr-codes.category-index', [
            'categories' => auth()->user()->categories()
                ->withCount('qrCodes')
                ->orderBy('name')
                ->get(),
            'colorOptions' => Category::colorOptions(),
        ])->layout('layouts.app', ['title' => __('qr.categories')]);
    }
}
