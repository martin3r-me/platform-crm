<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        return view('crm::livewire.sidebar')->layout('platform::layouts.app');
    }
}