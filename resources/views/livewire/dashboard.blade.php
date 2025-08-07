<div>
    <h1 class="text-3xl font-bold mb-6">CRM Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
	    <x-ui-dashboard-tile 
	        title="Kontakte" 
	        :count="$stats['contacts']" 
	        icon="users"
	        variant="success"
	        :href="route('crm.contacts.index')"
	        clickable="true"
	    />
	    
	    <x-ui-dashboard-tile 
	        title="Unternehmen" 
	        :count="$stats['companies']" 
	        icon="building-office"
	        variant="primary"
	        :href="route('crm.companies.index')"
	        clickable="true"
	    />
	</div>
</div>