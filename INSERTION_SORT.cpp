/* Q: Write a program to accept N numbers in an array, and then sort the array using Insertion Sort. Then accept a number
      from the user and insert it in the array according to the sequential order .*/
#include<iostream>
using namespace std;
void insertionsort(int arr[],int size)
{
	int max,j;
	for(int i=1;i<size;i++)
	{
	   max=arr[i];
	   j=i-1;
	   while(j>=0 && arr[j]>max)
	   {
	   	  arr[j+1]=arr[j];
	   	  j=j-1;
	   }
	   arr[j+1]=max;
	}
}
void print(int arr[],int n)
{
	for(int i=0;i<n;i++)
	{
	   cout<<arr[i]<<" ";
	}
	cout<<endl;
}
void insert(int arr[],int size,int ele)
{

	arr[size-1]=ele;

}
int main()
{
	int size,ele;
	cout<<"Enter the size of array : ";
	cin>>size;
	int arr[size];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<size;i++)
	   cin>>arr[i];
	insertionsort(arr,size);
	cout<<"Sorted Array : ";
	print(arr,size);
	cout<<"Enter the element to be inserted : ";
	cin>>ele;
	size++;
	insert(arr,size,ele);
	cout<<"Array after insertion : ";
	insertionsort(arr,size);
	print(arr,size);
	return 0;
}
