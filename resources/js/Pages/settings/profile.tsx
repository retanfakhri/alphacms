import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Camera, Facebook, Instagram, Linkedin, Globe, Twitter, Youtube, Phone, MessageCircle, Shield, Bell, Languages, Settings } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { edit } from '@/routes/profile';
import { useState } from 'react';

export default function Profile({
    mustVerifyEmail,
    status,
    user,
    topics = [],
    notificationTypes = [],
}: {
    mustVerifyEmail: boolean;
    status?: string;
    user: any;
    topics: { name: string, value: string }[];
    notificationTypes: { name: string, value: string }[];
}) {
    const [avatarPreview, setAvatarPreview] = useState(user?.avatar_url || '');
    const [coverPreview, setCoverPreview] = useState(user?.cover_url || '');

    return (
        <div className="max-w-4xl mx-auto px-4 py-8">
            <Head title="Profile Settings" />

            <div className="relative mb-8 group">
                <Form
                    {...ProfileController.updateImages.form()}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, setData, dirty }) => (
                        <>
                            <div className="h-48 md:h-64 w-full rounded-none overflow-hidden bg-muted">
                                <img src={coverPreview} alt="Cover" className="w-full h-full object-cover" />
                                <label className="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <Camera className="w-8 h-8 text-white" />
                                    <input type="file" className="hidden" name="cover" onChange={(e) => {
                                        const file = e.target.files?.[0];
                                        if (file) {
                                            setCoverPreview(URL.createObjectURL(file));
                                            setData('cover', file);
                                        }
                                    }} />
                                </label>
                            </div>
                            <div className="absolute -bottom-16 left-8 p-1 bg-background rounded-none">
                                <div className="relative group/avatar">
                                    <Avatar className="h-32 w-32 border-4 border-background shadow-lg">
                                        <AvatarImage src={avatarPreview} />
                                        <AvatarFallback className="text-2xl font-bold">{user?.name?.charAt(0) || 'U'}</AvatarFallback>
                                    </Avatar>
                                    <label className="absolute inset-0 flex items-center justify-center bg-black/40 rounded-none opacity-0 group-hover/avatar:opacity-100 transition-opacity cursor-pointer">
                                        <Camera className="w-6 h-6 text-white" />
                                        <input type="file" className="hidden" name="avatar" onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (file) {
                                                setAvatarPreview(URL.createObjectURL(file));
                                                setData('avatar', file);
                                            }
                                        }} />
                                    </label>
                                </div>
                            </div>
                            {dirty && (
                                <div className="absolute top-4 right-4 animate-in fade-in slide-in-from-top-4">
                                    <Button size="sm" disabled={processing} className="shadow-lg">
                                        {processing ? 'Saving...' : 'Save Images'}
                                    </Button>
                                </div>
                            )}
                        </>
                    )}
                </Form>
            </div>

            <div className="mt-20 flex justify-between items-end mb-8">
                <div>
                    <h1 className="text-2xl font-bold">{user.name}</h1>
                    <p className="text-muted-foreground">@{user.username}</p>
                </div>
                <div className="space-x-2">
                   {/* Actions if needed */}
                </div>
            </div>

            <Tabs defaultValue="personal" className="w-full">
                <TabsList className="grid w-full grid-cols-4 mb-8">
                    <TabsTrigger value="personal">Personal Info</TabsTrigger>
                    <TabsTrigger value="social">Social Media</TabsTrigger>
                    <TabsTrigger value="preferences">Preferences</TabsTrigger>
                    <TabsTrigger value="account">Account</TabsTrigger>
                </TabsList>

                <TabsContent value="personal" className="space-y-6">
                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6 bg-card p-6 rounded-none border shadow-sm"
                    >
                        {({ processing, errors, data, setData }) => (
                            <>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Full Name</Label>
                                        <Input id="name" name="name" value={data.name ?? user?.name ?? ''} onChange={e => setData('name', e.target.value)} required />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="username">Username</Label>
                                        <Input id="username" name="username" value={data.username ?? user?.username ?? ''} onChange={e => setData('username', e.target.value)} required />
                                        <InputError message={errors.username} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email Address</Label>
                                        <Input id="email" name="email" type="email" value={data.email ?? user?.email ?? ''} onChange={e => setData('email', e.target.value)} required />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Phone Number</Label>
                                        <div className="flex gap-2">
                                            <Input id="phone_code" name="phone_code" className="w-20" placeholder="+966" value={data.phone_code ?? user?.phone_code ?? ''} onChange={e => setData('phone_code', e.target.value)} />
                                            <Input id="phone" name="phone" className="flex-1" placeholder="50xxxxxxx" value={data.phone ?? user?.phone ?? ''} onChange={e => setData('phone', e.target.value)} />
                                        </div>
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox 
                                        id="has_whatsapp" 
                                        checked={data.has_whatsapp ?? user.has_whatsapp} 
                                        onCheckedChange={(checked) => setData('has_whatsapp', !!checked)}
                                    />
                                    <Label htmlFor="has_whatsapp" className="flex items-center gap-2 cursor-pointer">
                                        <MessageCircle className="w-4 h-4 text-green-500" />
                                        I have WhatsApp on this number
                                    </Label>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="bio">Bio</Label>
                                    <Textarea id="bio" name="bio" value={data.bio ?? user?.bio ?? ''} onChange={e => setData('bio', e.target.value)} placeholder="Tell us about yourself..." className="h-32" />
                                    <InputError message={errors.bio} />
                                </div>

                                <Button disabled={processing}>Save Changes</Button>
                            </>
                        )}
                    </Form>
                </TabsContent>

                <TabsContent value="social" className="space-y-6">
                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6 bg-card p-6 rounded-none border shadow-sm"
                    >
                        {({ processing, errors, data, setData }) => (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Facebook className="w-4 h-4 text-blue-600" /> Facebook</Label>
                                    <Input name="facebook_url" value={data.facebook_url ?? user.facebook_url ?? ''} onChange={e => setData('facebook_url', e.target.value)} placeholder="https://facebook.com/..." />
                                    <InputError message={errors.facebook_url} />
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Twitter className="w-4 h-4 text-sky-500" /> X (Twitter)</Label>
                                    <Input name="x_url" value={data.x_url ?? user.x_url ?? ''} onChange={e => setData('x_url', e.target.value)} placeholder="https://x.com/..." />
                                    <InputError message={errors.x_url} />
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Instagram className="w-4 h-4 text-pink-600" /> Instagram</Label>
                                    <Input name="instagram_url" value={data.instagram_url ?? user.instagram_url ?? ''} onChange={e => setData('instagram_url', e.target.value)} placeholder="https://instagram.com/..." />
                                    <InputError message={errors.instagram_url} />
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Linkedin className="w-4 h-4 text-blue-700" /> LinkedIn</Label>
                                    <Input name="linkedin_url" value={data.linkedin_url ?? user.linkedin_url ?? ''} onChange={e => setData('linkedin_url', e.target.value)} placeholder="https://linkedin.com/in/..." />
                                    <InputError message={errors.linkedin_url} />
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Youtube className="w-4 h-4 text-red-600" /> YouTube</Label>
                                    <Input name="youtube_url" value={data.youtube_url ?? user.youtube_url ?? ''} onChange={e => setData('youtube_url', e.target.value)} placeholder="https://youtube.com/@..." />
                                    <InputError message={errors.youtube_url} />
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-2"><Globe className="w-4 h-4" /> Website</Label>
                                    <Input name="website_url" value={data.website_url ?? user.website_url ?? ''} onChange={e => setData('website_url', e.target.value)} placeholder="https://example.com" />
                                    <InputError message={errors.website_url} />
                                </div>
                                <div className="md:col-span-2">
                                    <Button disabled={processing}>Update Social Links</Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </TabsContent>

                <TabsContent value="preferences" className="space-y-6">
                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6 bg-card p-6 rounded-none border shadow-sm"
                    >
                        {({ processing, errors, data, setData }) => (
                            <div className="space-y-8">
                                <div className="space-y-4">
                                    <Heading variant="small" title="Language & Region" description="Choose your preferred language for the interface." />
                                    <div className="max-w-xs space-y-2">
                                        <Label htmlFor="preferred_locale">Language</Label>
                                        <Select value={data.preferred_locale ?? user?.preferred_locale ?? 'en'} onValueChange={val => setData('preferred_locale', val)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Language" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="en">English</SelectItem>
                                                <SelectItem value="ar">العربية (Arabic)</SelectItem>
                                                <SelectItem value="fr">Français</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.preferred_locale} />
                                    </div>
                                </div>

                                <div className="space-y-4 border-t pt-8">
                                    <Heading variant="small" title="Interests" description="Select topics you are interested in." />
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        {topics.map(topic => (
                                            <div key={topic.value} className="flex items-center space-x-2">
                                                <Checkbox 
                                                    id={`topic-${topic.value}`}
                                                    checked={(data.preferred_topics ?? user?.preferred_topics ?? []).includes(topic.value)}
                                                    onCheckedChange={(checked) => {
                                                        const current = data.preferred_topics ?? user.preferred_topics ?? [];
                                                        if (checked) {
                                                            setData('preferred_topics', [...current, topic.value]);
                                                        } else {
                                                            setData('preferred_topics', current.filter((t: string) => t !== topic.value));
                                                        }
                                                    }}
                                                />
                                                <Label htmlFor={`topic-${topic.value}`} className="cursor-pointer">{topic.name}</Label>
                                            </div>
                                        ))}
                                    </div>
                                    <InputError message={errors.preferred_topics} />
                                </div>

                                <div className="space-y-4 border-t pt-8">
                                    <Heading variant="small" title="Notifications" description="Manage how you receive notifications." />
                                    <div className="space-y-4">
                                        {notificationTypes.map(pref => (
                                            <div key={pref.value} className="flex items-start space-x-3">
                                                <Checkbox 
                                                    id={pref.value}
                                                    checked={(data.notification_preferences ?? user?.notification_preferences ?? {})[pref.value] ?? false}
                                                    onCheckedChange={(checked) => {
                                                        const current = data.notification_preferences ?? user.notification_preferences ?? {};
                                                        setData('notification_preferences', { ...current, [pref.value]: !!checked });
                                                    }}
                                                />
                                                <div className="grid gap-1.5 leading-none">
                                                    <Label htmlFor={pref.value} className="font-medium cursor-pointer">{pref.name}</Label>
                                                    <p className="text-xs text-muted-foreground">Receive updates for {pref.name.toLowerCase()}.</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    <InputError message={errors.notification_preferences} />
                                </div>

                                <Button disabled={processing}>Save Preferences</Button>
                            </div>
                        )}
                    </Form>
                </TabsContent>

                <TabsContent value="account" className="space-y-6">
                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6 bg-card p-6 rounded-none border shadow-sm"
                    >
                        {({ processing, errors, data, setData }) => (
                            <div className="space-y-8">
                                <div className="space-y-4">
                                    <Heading variant="small" title="Privacy Settings" description="Control your account visibility and interactions." />
                                    <div className="space-y-6">
                                        <div className="flex items-start space-x-3">
                                            <Checkbox 
                                                id="is_private"
                                                checked={data.is_private ?? user?.is_private ?? false}
                                                onCheckedChange={(checked) => setData('is_private', !!checked)}
                                            />
                                            <div className="grid gap-1.5 leading-none">
                                                <Label htmlFor="is_private" className="font-medium flex items-center gap-2 cursor-pointer">
                                                    <Shield className="w-4 h-4" /> Private Account
                                                </Label>
                                                <p className="text-xs text-muted-foreground">Only approved followers can see your profile and posts.</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start space-x-3">
                                            <Checkbox 
                                                id="comments_blocked"
                                                checked={data.comments_blocked ?? user?.comments_blocked ?? false}
                                                onCheckedChange={(checked) => setData('comments_blocked', !!checked)}
                                            />
                                            <div className="grid gap-1.5 leading-none">
                                                <Label htmlFor="comments_blocked" className="font-medium cursor-pointer">Block Comments</Label>
                                                <p className="text-xs text-muted-foreground">Prevent others from commenting on your posts.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <InputError message={errors.is_private || errors.comments_blocked} />
                                </div>

                                <div className="pt-4">
                                    <Button disabled={processing}>Update Privacy Settings</Button>
                                </div>
                            </div>
                        )}
                    </Form>

                    <div className="bg-card p-6 rounded-none border border-destructive/20 shadow-sm">
                        <Heading
                            variant="small"
                            title="Delete Account"
                            description="Once your account is deleted, all of its resources and data will be permanently deleted."
                        />
                        <div className="mt-6">
                            <DeleteUser />
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile Settings',
            href: '/user/profile-settings',
        },
    ],
};
