<?php

namespace App\Http\Controllers;

use App\Models\QadSetting;
use Illuminate\Http\Request;

class QadSettingController extends Controller
{
    public function edit()
    {
        $setting = QadSetting::current();
        return view('admin.qad-settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'qad_soap_url' => 'nullable|url|max:255',
            'qad_wsa_url'  => 'nullable|url|max:255',
        ]);

        $setting = QadSetting::current();
        $setting->update([
            'qad_soap_url' => $request->qad_soap_url ?: null,
            'qad_wsa_url'  => $request->qad_wsa_url ?: null,
        ]);

        return back()->with('success', 'URL QAD berhasil diperbarui.');
    }
}
